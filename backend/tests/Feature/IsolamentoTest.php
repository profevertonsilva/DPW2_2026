<?php

/*
| RF #37 / RNF #02: isolamento entre barbearias (seção 9.1).
| O dono da barbearia A recebe 404 ao acessar recursos da barbearia B.
*/

beforeEach(function () {
    $this->donoA = conta('Dono A');
    $this->a = barbearia($this->donoA, ['nome' => 'Barbearia A']);
    $this->b = barbearia(conta('Dono B'), ['nome' => 'Barbearia B']);
    $this->servicoB = servico($this->b);
    $this->barbeiroB = barbeiro($this->b, [$this->servicoB]);
    $this->atendimentoB = atendimento($this->barbeiroB, $this->servicoB, '2026-11-17 10:00');
    entrarComo($this->donoA);
});

it('responde 404 para as rotas da barbearia B', function (string $metodo, string $rota) {
    $rota = strtr($rota, [
        '{b}' => $this->b->id,
        '{servico}' => $this->servicoB->id,
        '{vinculo}' => $this->barbeiroB->id,
        '{atendimento}' => $this->atendimentoB->id,
    ]);

    $this->json($metodo, "/api/painel/estabelecimentos/{$rota}", [])->assertNotFound();
})->with([
    ['GET', '{b}'],
    ['PUT', '{b}'],
    ['GET', '{b}/servicos'],
    ['POST', '{b}/servicos'],
    ['PUT', '{b}/servicos/{servico}'],
    ['DELETE', '{b}/servicos/{servico}'],
    ['GET', '{b}/equipe'],
    ['DELETE', '{b}/equipe/{vinculo}'],
    ['GET', '{b}/agenda?data=2026-11-17'],
    ['GET', '{b}/agendamentos/{atendimento}'],
    ['POST', '{b}/agendamentos/{atendimento}/realizado'],
    ['POST', '{b}/agendamentos/{atendimento}/cancelar'],
]);

it('responde 404 para recurso da barbearia B pendurado na rota da barbearia A (scoped bindings)', function () {
    $base = "/api/painel/estabelecimentos/{$this->a->id}";

    $this->putJson("{$base}/servicos/{$this->servicoB->id}", ['nome' => 'X', 'preco' => 10, 'duracao_minutos' => 30])->assertNotFound();
    $this->deleteJson("{$base}/servicos/{$this->servicoB->id}")->assertNotFound();
    $this->deleteJson("{$base}/equipe/{$this->barbeiroB->id}")->assertNotFound();
    $this->putJson("{$base}/equipe/{$this->barbeiroB->id}/servicos", ['servico_ids' => [$this->servicoB->id]])->assertNotFound();
    $this->getJson("{$base}/agendamentos/{$this->atendimentoB->id}")->assertNotFound();
    $this->postJson("{$base}/agendamentos/{$this->atendimentoB->id}/cancelar")->assertNotFound();

    expect($this->servicoB->refresh()->status)->toBe('a')
        ->and($this->atendimentoB->refresh()->status)->toBe('agendado');
});

it('não mostra na agenda da barbearia A os atendimentos da B', function () {
    $this->getJson("/api/painel/estabelecimentos/{$this->a->id}/agenda?data=2026-11-17")
        ->assertOk()->assertExactJson([]);
});

it('não deixa o proprietário atribuir à sua equipe um serviço de outra barbearia', function () {
    $meuBarbeiro = barbeiro($this->a);

    $this->putJson("/api/painel/estabelecimentos/{$this->a->id}/equipe/{$meuBarbeiro->id}/servicos", ['servico_ids' => [$this->servicoB->id]])
        ->assertUnprocessable()->assertJsonValidationErrors(['servico_ids']);
});

it('lista no painel apenas as barbearias com vínculo do usuário', function () {
    $this->getJson('/api/painel/estabelecimentos')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.nome', 'Barbearia A')
        ->assertJsonPath('0.papel', 'proprietario');
});
