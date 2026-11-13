<?php

use App\Models\Atendimento;
use App\Models\Avaliacao;

/*
| RF #30: avaliação após atendimento realizado (desejável 2, telas 26 e 06).
*/

beforeEach(function () {
    $this->barbearia = barbearia(sobrescrever: ['nome' => 'Barbearia Central']);
    $this->corte = servico($this->barbearia, nome: 'Corte de cabelo');
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->cliente = entrarComo(conta('Mariana Souza'));
    $this->realizado = atendimento($this->barbeiro, $this->corte, '2026-11-10 10:00', $this->cliente, Atendimento::REALIZADO);
});

it('avalia um atendimento realizado e atualiza a média da barbearia', function () {
    $this->putJson("/api/agendamentos/{$this->realizado->id}/avaliacao", ['nota' => 5, 'comentario' => 'Atendimento impecável.'])
        ->assertOk()
        ->assertJsonPath('avaliacao.nota', 5)
        ->assertJsonPath('avaliacao.comentario', 'Atendimento impecável.')
        ->assertJsonPath('pode_avaliar', true);

    $outro = atendimento($this->barbeiro, $this->corte, '2026-11-11 10:00', conta('Rafael Teixeira'), Atendimento::REALIZADO);
    Avaliacao::create(['atendimento_id' => $outro->id, 'nota' => 4, 'status' => 'a']);

    $this->getJson("/api/barbearias/{$this->barbearia->id}")
        ->assertJsonPath('avaliacao', ['media' => 4.5, 'total' => 2]);
    $this->getJson('/api/barbearias?busca=central')
        ->assertJsonPath('0.avaliacao', ['media' => 4.5, 'total' => 2]);

    $this->getJson("/api/barbearias/{$this->barbearia->id}/avaliacoes")
        ->assertOk()
        ->assertJsonPath('media', 4.5)
        ->assertJsonPath('total', 2)
        ->assertJsonCount(2, 'avaliacoes')
        ->assertJsonPath('avaliacoes.0.cliente', 'Mariana S.')
        ->assertJsonPath('avaliacoes.0.servico', 'Corte de cabelo');
});

it('só aceita avaliação de atendimento realizado', function () {
    $futuro = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', $this->cliente);
    $cancelado = atendimento($this->barbeiro, $this->corte, '2026-11-12 10:00', $this->cliente, Atendimento::CANCELADO);

    $this->putJson("/api/agendamentos/{$futuro->id}/avaliacao", ['nota' => 5])
        ->assertUnprocessable()->assertJsonPath('errors.nota.0', 'Só é possível avaliar um atendimento realizado.');
    $this->putJson("/api/agendamentos/{$cancelado->id}/avaliacao", ['nota' => 5])->assertUnprocessable();

    $this->getJson("/api/agendamentos/{$futuro->id}")->assertJsonPath('pode_avaliar', false);
});

it('valida a nota de 1 a 5', function (mixed $nota) {
    $this->putJson("/api/agendamentos/{$this->realizado->id}/avaliacao", ['nota' => $nota])
        ->assertUnprocessable()->assertJsonValidationErrors(['nota']);
})->with([0, 6, 'cinco', null]);

it('mantém uma avaliação por atendimento e permite editar em até 7 dias', function () {
    $url = "/api/agendamentos/{$this->realizado->id}/avaliacao";
    $this->putJson($url, ['nota' => 3])->assertOk();
    $this->putJson($url, ['nota' => 4, 'comentario' => 'Melhorou na conversa.'])->assertOk()->assertJsonPath('avaliacao.nota', 4);

    expect(Avaliacao::count())->toBe(1);

    $this->travel(8)->days();
    $this->putJson($url, ['nota' => 1])
        ->assertUnprocessable()->assertJsonPath('errors.nota.0', 'O prazo de 7 dias para editar esta avaliação terminou.');
    $this->getJson("/api/agendamentos/{$this->realizado->id}")->assertJsonPath('pode_avaliar', false);
    expect(Avaliacao::first()->nota)->toBe(4);
});

it('não deixa avaliar atendimento de outro cliente', function () {
    entrarComo(conta('Intruso'));

    $this->putJson("/api/agendamentos/{$this->realizado->id}/avaliacao", ['nota' => 1])->assertForbidden();
    expect(Avaliacao::count())->toBe(0);
});

it('esconde avaliações com status "i" da lista e da média', function () {
    Avaliacao::create(['atendimento_id' => $this->realizado->id, 'nota' => 1, 'status' => 'i']);

    $this->getJson("/api/barbearias/{$this->barbearia->id}/avaliacoes")
        ->assertJsonPath('total', 0)->assertJsonPath('media', null)->assertJsonCount(0, 'avaliacoes');
});
