<?php

use App\Models\BloqueioHorario;

/*
| RF #14: bloqueio de horários (desejável 1, tela 41).
| Hoje é segunda, 16/11/2026, 08:00; a barbearia abre das 09:00 às 18:00.
*/

beforeEach(function () {
    $this->dono = conta('Dono');
    $this->barbearia = barbearia($this->dono);
    $this->corte = servico($this->barbearia);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->colega = barbeiro($this->barbearia, [$this->corte]);
    $this->base = "/api/painel/estabelecimentos/{$this->barbearia->id}/bloqueios";
});

it('deixa o barbeiro bloquear a própria agenda e tira os horários da grade', function () {
    entrarComo(loginDo($this->barbeiro));

    $this->postJson($this->base, [
        'vinculo_id' => $this->barbeiro->id,
        'inicio' => sp('2026-11-17 12:00'),
        'fim' => sp('2026-11-17 13:30'),
        'motivo' => 'Consulta médica',
    ])->assertCreated()
        ->assertJsonPath('bloqueio.hora_inicio', '12:00')
        ->assertJsonPath('bloqueio.hora_fim', '13:30')
        ->assertJsonPath('bloqueio.motivo', 'Consulta médica')
        ->assertJsonPath('conflitos', []);

    expect(grade($this->barbeiro, $this->corte, '2026-11-17'))
        ->toContain('11:30', '13:30')->not->toContain('12:00', '12:30', '13:00');
});

it('não deixa o barbeiro bloquear a agenda de outro barbeiro', function () {
    entrarComo(loginDo($this->barbeiro));

    $this->postJson($this->base, ['vinculo_id' => $this->colega->id, 'inicio' => sp('2026-11-17 12:00'), 'fim' => sp('2026-11-17 13:00')])
        ->assertForbidden();
});

it('deixa o proprietário bloquear qualquer barbeiro e lista os bloqueios do dia', function () {
    entrarComo($this->dono);

    $this->postJson($this->base, ['vinculo_id' => $this->colega->id, 'inicio' => sp('2026-11-17 09:00'), 'fim' => sp('2026-11-17 10:00')])->assertCreated();
    $this->postJson($this->base, ['vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-18 09:00'), 'fim' => sp('2026-11-18 10:00')])->assertCreated();

    $this->getJson("{$this->base}?de=2026-11-17")->assertOk()->assertJsonCount(1)->assertJsonPath('0.vinculo_id', $this->colega->id);
    $this->getJson("{$this->base}?de=2026-11-17&ate=2026-11-18")->assertOk()->assertJsonCount(2);
});

it('mostra ao barbeiro só os próprios bloqueios', function () {
    bloqueio($this->barbeiro, '2026-11-17 09:00', '2026-11-17 10:00');
    bloqueio($this->colega, '2026-11-17 09:00', '2026-11-17 10:00');
    entrarComo(loginDo($this->barbeiro));

    $this->getJson("{$this->base}?de=2026-11-17&vinculo_id={$this->colega->id}")
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.vinculo_id', $this->barbeiro->id);
});

it('não cancela agendamentos existentes: devolve os conflitos para decisão (RF #14)', function () {
    $dentro = atendimento($this->barbeiro, $this->corte, '2026-11-17 12:00');
    atendimento($this->barbeiro, $this->corte, '2026-11-17 14:00'); // fora do intervalo
    atendimento($this->colega, $this->corte, '2026-11-17 12:00');   // outro barbeiro
    entrarComo($this->dono);
    $pedido = ['vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 11:30'), 'fim' => sp('2026-11-17 13:00')];

    $this->getJson("{$this->base}/conflitos?".http_build_query($pedido))
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $dentro->id);

    $this->postJson($this->base, $pedido)
        ->assertCreated()
        ->assertJsonCount(1, 'conflitos')
        ->assertJsonPath('conflitos.0.id', $dentro->id);

    expect($dentro->refresh()->status)->toBe('agendado');
});

it('valida o intervalo e o barbeiro do bloqueio', function () {
    entrarComo($this->dono);

    $this->postJson($this->base, ['vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 13:00'), 'fim' => sp('2026-11-17 12:00')])
        ->assertUnprocessable()->assertJsonPath('errors.fim.0', 'O fim do bloqueio deve ser depois do início.');

    $outra = barbearia();
    $deFora = barbeiro($outra);
    $this->postJson($this->base, ['vinculo_id' => $deFora->id, 'inicio' => sp('2026-11-17 12:00'), 'fim' => sp('2026-11-17 13:00')])
        ->assertUnprocessable()->assertJsonValidationErrors(['vinculo_id']);
});

it('bloqueia vários dias seguidos e libera a grade ao remover', function () {
    entrarComo($this->dono);
    $id = $this->postJson($this->base, ['vinculo_id' => $this->barbeiro->id, 'inicio' => sp('2026-11-17 09:00'), 'fim' => sp('2026-11-19 18:00')])
        ->assertCreated()->json('bloqueio.id');

    expect(grade($this->barbeiro, $this->corte, '2026-11-18'))->toBe([]);

    $this->deleteJson("{$this->base}/{$id}")->assertOk();
    expect(BloqueioHorario::count())->toBe(0)
        ->and(grade($this->barbeiro, $this->corte, '2026-11-18'))->not->toBeEmpty();
});

it('isola os bloqueios entre barbearias e entre barbeiros', function () {
    $outraBarbearia = barbearia();
    $bloqueioDeFora = bloqueio(barbeiro($outraBarbearia), '2026-11-17 09:00', '2026-11-17 10:00');
    $doColega = bloqueio($this->colega, '2026-11-17 09:00', '2026-11-17 10:00');

    entrarComo($this->dono);
    $this->deleteJson("{$this->base}/{$bloqueioDeFora->id}")->assertNotFound();

    entrarComo(loginDo($this->barbeiro));
    $this->deleteJson("{$this->base}/{$doColega->id}")->assertForbidden();
    expect(BloqueioHorario::count())->toBe(2);
});
