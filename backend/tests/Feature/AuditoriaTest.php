<?php

use App\Models\Auditoria;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/*
| RF #38: trilha de auditoria (desejável 6, tela 46).
*/

beforeEach(function () {
    $this->dono = conta('Carlos Dono');
    $this->barbearia = barbearia($this->dono);
    $this->corte = servico($this->barbearia);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte], conta('Rafael Barbeiro'));
    $this->cliente = conta('Lucas Cliente');
    $this->url = "/api/painel/estabelecimentos/{$this->barbearia->id}/auditoria";
});

it('registra cancelamento e remarcação feitos pelo cliente, com o responsável', function () {
    $a = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);
    $b = atendimento($this->barbeiro, $this->corte, '2026-11-18 10:00', $this->cliente);
    entrarComo($this->cliente);

    $this->postJson("/api/agendamentos/{$a->id}/remarcar", ['inicio' => sp('2026-11-17 15:00')])->assertOk();
    $this->postJson("/api/agendamentos/{$b->id}/cancelar", ['motivo' => 'Viagem'])->assertOk();

    entrarComo($this->dono);
    $this->getJson($this->url)
        ->assertOk()
        ->assertJsonCount(2, 'registros')
        ->assertJsonPath('registros.0.operacao', 'Cancelamento')
        ->assertJsonPath('registros.0.usuario.nome', 'Lucas Cliente')
        ->assertJsonPath('registros.0.entidade_nome', 'Agendamento')
        ->assertJsonPath('registros.0.entidade_afetada', $b->id)
        ->assertJsonPath('registros.0.valor_anterior', ['status' => 'agendado'])
        // JSONB não preserva a ordem das chaves: compara campo a campo.
        ->assertJsonPath('registros.0.valor_novo.status', 'cancelado')
        ->assertJsonPath('registros.0.valor_novo.motivo', 'Viagem')
        ->assertJsonPath('registros.1.operacao', 'Remarcação')
        ->assertJsonPath('registros.1.valor_anterior', ['inicio' => '17/11/2026 10:00'])
        ->assertJsonPath('registros.1.valor_novo', ['inicio' => '17/11/2026 15:00']);
});

it('registra cancelamento pelo painel, remoção de colaborador e alteração da barbearia', function () {
    $a = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);
    $base = "/api/painel/estabelecimentos/{$this->barbearia->id}";

    entrarComo(loginDo($this->barbeiro));
    $this->postJson("{$base}/agendamentos/{$a->id}/cancelar")->assertOk();

    entrarComo($this->dono);
    $this->deleteJson("{$base}/equipe/{$this->barbeiro->id}")->assertOk();
    $this->putJson($base, dadosBarbearia(['nome' => 'Barbearia Nova', 'horarios' => semana([0 => null])]))->assertOk();

    $registros = $this->getJson($this->url)->assertOk()->json('registros');
    expect(array_column($registros, 'operacao'))->toBe(['Alteração da barbearia', 'Remoção de colaborador', 'Cancelamento'])
        ->and($registros[0]['valor_anterior'])->toBe(['nome' => 'Barbearia Teste', 'horario_dom' => '09:00-18:00'])
        ->and($registros[0]['valor_novo'])->toBe(['nome' => 'Barbearia Nova', 'horario_dom' => 'fechado'])
        ->and($registros[1]['valor_anterior']['colaborador'])->toBe('Rafael Barbeiro')
        ->and($registros[2]['usuario']['nome'])->toBe('Rafael Barbeiro');
});

it('não registra edição da barbearia sem mudança', function () {
    entrarComo($this->dono);
    $this->putJson("/api/painel/estabelecimentos/{$this->barbearia->id}", dadosBarbearia())->assertOk();

    expect(Auditoria::count())->toBe(0);
});

it('filtra por usuário e por período', function () {
    $a = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);
    $b = atendimento($this->barbeiro, $this->corte, '2026-11-18 10:00');
    entrarComo($this->cliente);
    $this->postJson("/api/agendamentos/{$a->id}/cancelar")->assertOk();
    $this->travel(2)->days();
    entrarComo($this->dono);
    $this->postJson("/api/painel/estabelecimentos/{$this->barbearia->id}/agendamentos/{$b->id}/cancelar")->assertOk();

    $this->getJson("{$this->url}?usuario_id={$this->cliente->id}")->assertJsonCount(1, 'registros')
        ->assertJsonPath('registros.0.usuario.nome', 'Lucas Cliente')
        ->assertJsonCount(2, 'usuarios');
    $this->getJson("{$this->url}?de=2026-11-18&ate=2026-11-18")->assertJsonCount(1, 'registros')
        ->assertJsonPath('registros.0.usuario.nome', 'Carlos Dono');
});

it('é consulta só do proprietário e de cada barbearia', function () {
    entrarComo(loginDo($this->barbeiro));
    $this->getJson($this->url)->assertForbidden();

    $outra = barbearia(conta('Outro Dono'));
    entrarComo($this->dono);
    $this->getJson("/api/painel/estabelecimentos/{$outra->id}/auditoria")->assertNotFound();
});

it('não deixa alterar nem excluir registros, nem pelo banco', function () {
    $a = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);
    entrarComo($this->cliente);
    $this->postJson("/api/agendamentos/{$a->id}/cancelar")->assertOk();
    $id = Auditoria::sole()->id;

    expect(fn () => DB::transaction(fn () => DB::table('auditoria')->where('id', $id)->update(['tipo_operacao' => 'x'])))
        ->toThrow(QueryException::class, 'não pode ser alterada nem excluída');
    expect(fn () => DB::transaction(fn () => DB::table('auditoria')->where('id', $id)->delete()))
        ->toThrow(QueryException::class, 'não pode ser alterada nem excluída');
    expect(Auditoria::sole()->tipo_operacao)->toBe('cancelamento');
});
