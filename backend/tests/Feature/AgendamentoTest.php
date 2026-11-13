<?php

use App\Models\Atendimento;
use Illuminate\Support\Facades\DB;

/*
| RF #18, #21, #23, #33: fluxo do agendamento.
| Hoje é segunda, 16/11/2026, 08:00; a barbearia abre das 09:00 às 18:00.
*/

beforeEach(function () {
    $this->barbearia = barbearia();
    $this->corte = servico($this->barbearia, 30, 40.0);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->cliente = entrarComo(conta('Lucas'));
});

function pedido(array $sobrescrever = []): array
{
    return [
        'servico_id' => test()->corte->id,
        'vinculo_id' => test()->barbeiro->id,
        'inicio' => sp('2026-11-17 10:00'),
        ...$sobrescrever,
    ];
}

it('cria o agendamento com status agendado e o preço do momento', function () {
    $resposta = $this->postJson('/api/agendamentos', pedido())
        ->assertCreated()
        ->assertJsonPath('status', 'agendado')
        ->assertJsonPath('inicio', '2026-11-17T10:00:00-03:00')
        ->assertJsonPath('fim', '2026-11-17T10:30:00-03:00')
        ->assertJsonPath('data', '17/11/2026')
        ->assertJsonPath('hora_inicio', '10:00')
        ->assertJsonPath('valor', 40)
        ->assertJsonPath('servico.nome', 'Corte')
        ->assertJsonPath('barbearia.nome', 'Barbearia Teste')
        ->assertJsonPath('pode_cancelar', true);

    $atendimento = Atendimento::findOrFail($resposta->json('id'));
    expect($atendimento->servicos()->first()->pivot->valor)->toBe('40.00')
        ->and(DB::table('cliente_estabelecimento')->where('cliente_id', $this->cliente->cliente->id)->exists())->toBeTrue();

    // Mudar o preço depois não altera o agendamento.
    $this->corte->update(['preco' => 99]);
    $this->getJson("/api/agendamentos/{$atendimento->id}")->assertJsonPath('valor', 40);
});

it('aceita o início em outro fuso e grava o mesmo instante', function () {
    $this->postJson('/api/agendamentos', pedido(['inicio' => '2026-11-17T13:00:00Z']))
        ->assertCreated()
        ->assertJsonPath('inicio', '2026-11-17T10:00:00-03:00');
});

it('recusa início no passado ou fora do expediente (422)', function () {
    $this->postJson('/api/agendamentos', pedido(['inicio' => sp('2026-11-16 07:00')]))
        ->assertUnprocessable()->assertJsonPath('errors.inicio.0', 'Escolha um horário no futuro.');

    $this->postJson('/api/agendamentos', pedido(['inicio' => sp('2026-11-17 17:45')]))
        ->assertUnprocessable()->assertJsonPath('errors.inicio.0', 'Horário fora do expediente do barbeiro.');

    $this->postJson('/api/agendamentos', pedido(['inicio' => sp('2026-11-17 20:00')]))
        ->assertUnprocessable()->assertJsonValidationErrors(['inicio']);
});

it('recusa serviço que o barbeiro não executa, inativo ou de outra barbearia', function () {
    $barba = servico($this->barbearia, 30, 30, 'Barba');
    $this->postJson('/api/agendamentos', pedido(['servico_id' => $barba->id]))
        ->assertUnprocessable()->assertJsonPath('errors.servico_id.0', 'Este barbeiro não executa o serviço escolhido.');

    $this->corte->update(['status' => 'i']);
    $this->postJson('/api/agendamentos', pedido())->assertUnprocessable()->assertJsonValidationErrors(['servico_id']);

    $deOutra = servico(barbearia());
    $this->postJson('/api/agendamentos', pedido(['servico_id' => $deOutra->id]))->assertUnprocessable();
});

it('lista os próximos e o histórico do cliente', function () {
    $futuro = atendimento($this->barbeiro, $this->corte, '2026-11-18 10:00', $this->cliente);
    $passado = atendimento($this->barbeiro, $this->corte, '2026-11-10 10:00', $this->cliente, Atendimento::REALIZADO);
    $cancelado = atendimento($this->barbeiro, $this->corte, '2026-11-19 10:00', $this->cliente, Atendimento::CANCELADO);
    atendimento($this->barbeiro, $this->corte, '2026-11-18 11:00'); // de outro cliente

    $this->getJson('/api/agendamentos?periodo=proximos')->assertOk()
        ->assertJsonCount(1)->assertJsonPath('0.id', $futuro->id);

    $this->getJson('/api/agendamentos?periodo=historico')->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', $cancelado->id)
        ->assertJsonPath('1.id', $passado->id);
});

it('cancela o próprio agendamento futuro com motivo', function () {
    $meu = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);

    $this->postJson("/api/agendamentos/{$meu->id}/cancelar", ['motivo' => 'Viagem'])
        ->assertOk()
        ->assertJsonPath('status', 'cancelado')
        ->assertJsonPath('motivo_cancelamento', 'Viagem')
        ->assertJsonPath('pode_cancelar', false);

    expect($meu->refresh()->cancelada_em)->not->toBeNull();

    $this->postJson("/api/agendamentos/{$meu->id}/cancelar")->assertUnprocessable();
});

it('não deixa o cliente cancelar nem remarcar agendamento passado', function () {
    $passado = atendimento($this->barbeiro, $this->corte, '2026-11-16 07:00', $this->cliente);

    $this->postJson("/api/agendamentos/{$passado->id}/cancelar")->assertUnprocessable();
    $this->postJson("/api/agendamentos/{$passado->id}/remarcar", ['inicio' => sp('2026-11-17 10:00')])->assertUnprocessable();
});

it('remarca mantendo serviço, barbeiro e valor', function () {
    $meu = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);

    $this->postJson("/api/agendamentos/{$meu->id}/remarcar", ['inicio' => sp('2026-11-18 15:00')])
        ->assertOk()
        ->assertJsonPath('id', $meu->id)
        ->assertJsonPath('inicio', '2026-11-18T15:00:00-03:00')
        ->assertJsonPath('fim', '2026-11-18T15:30:00-03:00')
        ->assertJsonPath('barbeiro.vinculo_id', $this->barbeiro->id)
        ->assertJsonPath('valor', 40);
});

it('oferece na remarcação a grade que ignora o próprio atendimento', function () {
    $meu = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);

    expect($this->getJson("/api/agendamentos/{$meu->id}/disponibilidade?data=2026-11-17")->assertOk()->json('horarios.*.hora'))
        ->toContain('10:00');
    expect(grade($this->barbeiro, $this->corte, '2026-11-17'))->not->toContain('10:00');
});

it('marca como realizado só a partir do dia do atendimento e grava o instante', function () {
    $dono = loginDo(vinculoDoDono($this->barbearia));
    $hoje = atendimento($this->barbeiro, $this->corte, '2026-11-16 10:00');
    $amanha = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo($dono);
    $base = "/api/painel/estabelecimentos/{$this->barbearia->id}/agendamentos";

    $this->postJson("{$base}/{$amanha->id}/realizado")->assertUnprocessable();

    $this->postJson("{$base}/{$hoje->id}/realizado")->assertOk()->assertJsonPath('status', 'realizado');
    expect($hoje->refresh()->presenca_confirmada_em->toIso8601String())->toBe('2026-11-16T08:00:00-03:00');

    $this->postJson("{$base}/{$hoje->id}/realizado")->assertUnprocessable();
});

it('não aceita agendar com barbeiro removido da equipe', function () {
    $this->barbeiro->update(['status' => 'i', 'desvinculado_em' => now()]);

    $this->postJson('/api/agendamentos', pedido())
        ->assertUnprocessable()->assertJsonPath('errors.vinculo_id.0', 'Barbeiro não encontrado nesta barbearia.');
});
