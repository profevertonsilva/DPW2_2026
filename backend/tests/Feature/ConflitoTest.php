<?php

use App\Models\Atendimento;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
| RF #36 / RNF #01: proteção contra agendamentos sobrepostos (seção 9.1).
| Hoje é segunda, 16/11/2026, 08:00; a barbearia abre das 09:00 às 18:00.
*/

beforeEach(function () {
    $this->barbearia = barbearia();
    $this->corte = servico($this->barbearia, 30);
    $this->combo = servico($this->barbearia, 60, 65, 'Corte e barba');
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte, $this->combo]);
});

function agendar(array $dados)
{
    return test()->postJson('/api/agendamentos', $dados);
}

it('recusa com 409 a sobreposição total no mesmo barbeiro, com alternativas', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo(conta());

    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Este horário acabou de ser preenchido.')
        ->assertJsonPath('alternativas', [
            '2026-11-17T09:00:00-03:00',
            '2026-11-17T09:30:00-03:00',
            '2026-11-17T10:30:00-03:00',
            '2026-11-17T11:00:00-03:00',
        ]);
});

it('recusa com 409 a sobreposição parcial no mesmo barbeiro', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00'); // 10:00-10:30
    entrarComo(conta());

    // 09:30-10:30 invade o início do existente.
    agendar(['servico_id' => $this->combo->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 09:30')])
        ->assertStatus(409);

    // 10:15-10:45 invade o fim do existente (início fora da grade de 30 min).
    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:15')])
        ->assertStatus(409);
});

it('aceita horários encostados (intervalo semiaberto)', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00'); // 10:00-10:30
    entrarComo(conta());

    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:30')])
        ->assertCreated();
    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 09:30')])
        ->assertCreated();
});

it('aceita barbeiros diferentes no mesmo horário', function () {
    $outro = barbeiro($this->barbearia, [$this->corte]);
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo(conta());

    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $outro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertCreated();
});

it('libera o horário quando o agendamento é cancelado', function () {
    $dono = entrarComo(conta());
    $id = agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertCreated()->json('id');

    $this->postJson("/api/agendamentos/{$id}/cancelar")->assertOk()->assertJsonPath('status', 'cancelado');

    entrarComo(conta());
    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertCreated();
});

it('recusa com 409 a remarcação para um horário ocupado', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 14:00');
    $cliente = entrarComo(conta());
    $meu = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $cliente);

    $this->postJson("/api/agendamentos/{$meu->id}/remarcar", ['inicio' => sp('2026-11-17 14:00')])
        ->assertStatus(409)
        ->assertJsonStructure(['message', 'alternativas']);

    expect($meu->refresh()->inicio->format('H:i'))->toBe('10:00');
});

it('remarca para horário que sobrepõe apenas o próprio atendimento', function () {
    $cliente = entrarComo(conta());
    $meu = atendimento($this->barbeiro, $this->combo, '2026-11-17 10:00', $cliente); // 10:00-11:00

    $this->postJson("/api/agendamentos/{$meu->id}/remarcar", ['inicio' => sp('2026-11-17 10:30')])
        ->assertOk()
        ->assertJsonPath('hora_inicio', '10:30')
        ->assertJsonPath('hora_fim', '11:30');
});

it('tem a restrição de exclusão no banco, independente da aplicação', function () {
    $existente = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');

    $tentativa = fn () => DB::table('atendimento')->insert([
        'cliente_id' => $existente->cliente_id,
        'estabelecimento_id' => $existente->estabelecimento_id,
        'vinculo_estabelecimento_id' => $existente->vinculo_estabelecimento_id,
        'inicio' => '2026-11-17 10:15:00-03:00',
        'fim' => '2026-11-17 10:45:00-03:00',
        'status' => 'agendado',
    ]);

    expect(fn () => DB::transaction($tentativa))
        ->toThrow(fn (QueryException $e) => expect($e->getCode())->toBe('23P01')
            ->and($e->getMessage())->toContain('ex_atendimento_sem_sobreposicao'));
});

it('responde 409 quando a restrição do banco barra uma gravação concorrente', function () {
    entrarComo(conta());
    $concorrente = conta('Concorrente');

    // Simula outra requisição que grava o mesmo horário depois da verificação
    // prévia e antes do INSERT desta (a corrida que só o banco impede).
    $corridaSimulada = false;
    Atendimento::creating(function (Atendimento $novo) use ($concorrente, &$corridaSimulada) {
        Atendimento::flushEventListeners();
        atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $concorrente);
        $corridaSimulada = true;
    });

    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertStatus(409)
        ->assertJsonPath('message', 'Este horário acabou de ser preenchido.');

    // O listener só roda no INSERT, depois de a verificação prévia passar:
    // o 409 veio da violação 23P01 da restrição de exclusão.
    expect($corridaSimulada)->toBeTrue();

    // O rollback desfez tudo, inclusive a gravação concorrente da mesma transação,
    // e a conexão continua utilizável.
    expect(Atendimento::count())->toBe(0);
    entrarComo(conta());
    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 11:00')])
        ->assertCreated();
});

it('não conta atendimentos cancelados como ocupação', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', status: Atendimento::CANCELADO);
    entrarComo(conta());

    agendar(['servico_id' => $this->corte->id, 'vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 10:00')])
        ->assertCreated();
});
