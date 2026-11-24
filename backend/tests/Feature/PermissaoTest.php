<?php

/*
| RF #03: permissões por papel (seção 6.1 e 9.1).
*/

beforeEach(function () {
    $this->dono = conta('Dono');
    $this->barbearia = barbearia($this->dono);
    $this->corte = servico($this->barbearia);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->outroBarbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->base = "/api/painel/estabelecimentos/{$this->barbearia->id}";
});

it('não deixa o barbeiro cadastrar serviço (403)', function () {
    entrarComo(loginDo($this->barbeiro));

    $this->postJson("{$this->base}/servicos", ['nome' => 'Novo', 'preco' => 30, 'duracao_minutos' => 30])
        ->assertForbidden()
        ->assertJsonPath('message', 'Seu papel nesta barbearia não permite esta ação.');
});

it('não deixa o barbeiro gerir equipe nem editar a barbearia (403)', function () {
    entrarComo(loginDo($this->barbeiro));

    $this->getJson("{$this->base}/equipe")->assertForbidden();
    $this->postJson("{$this->base}/equipe", ['email' => 'x@teste.test'])->assertForbidden();
    $this->putJson($this->base, dadosBarbearia())->assertForbidden();
});

it('não deixa o cliente cancelar o agendamento de outro cliente', function () {
    $alheio = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00', conta('Outro cliente'));
    entrarComo(conta('Intruso'));

    $this->postJson("/api/agendamentos/{$alheio->id}/cancelar")->assertForbidden();
    $this->postJson("/api/agendamentos/{$alheio->id}/remarcar", ['inicio' => sp('2026-11-17 15:00')])->assertForbidden();
    $this->getJson("/api/agendamentos/{$alheio->id}")->assertForbidden();

    expect($alheio->refresh()->status)->toBe('agendado');
});

it('mostra ao barbeiro só a própria agenda', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    atendimento($this->outroBarbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo(loginDo($this->barbeiro));

    // Mesmo pedindo a agenda do colega, recebe só a própria.
    $this->getJson("{$this->base}/agenda?data=2026-11-17&vinculo_id={$this->outroBarbeiro->id}")
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.barbeiro.vinculo_id', $this->barbeiro->id);
});

it('mostra ao proprietário a agenda de toda a barbearia', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    atendimento($this->outroBarbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo($this->dono);

    $this->getJson("{$this->base}/agenda?data=2026-11-17")->assertOk()->assertJsonCount(2);
    $this->getJson("{$this->base}/agenda?data=2026-11-17&vinculo_id={$this->outroBarbeiro->id}")->assertOk()->assertJsonCount(1);
});

it('não deixa o barbeiro agir sobre atendimento de outro barbeiro', function () {
    $doColega = atendimento($this->outroBarbeiro, $this->corte, '2026-11-16 10:00');
    entrarComo(loginDo($this->barbeiro));

    $this->getJson("{$this->base}/agendamentos/{$doColega->id}")->assertForbidden();
    $this->postJson("{$this->base}/agendamentos/{$doColega->id}/realizado")->assertForbidden();
    $this->postJson("{$this->base}/agendamentos/{$doColega->id}/cancelar")->assertForbidden();
});

it('deixa o barbeiro marcar como realizado e cancelar os próprios atendimentos', function () {
    $hoje = atendimento($this->barbeiro, $this->corte, '2026-11-16 10:00');
    $amanha = atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');
    entrarComo(loginDo($this->barbeiro));

    $this->postJson("{$this->base}/agendamentos/{$hoje->id}/realizado")->assertOk()->assertJsonPath('status', 'realizado');
    $this->postJson("{$this->base}/agendamentos/{$amanha->id}/cancelar", ['motivo' => 'Imprevisto'])
        ->assertOk()->assertJsonPath('status', 'cancelado')->assertJsonPath('motivo_cancelamento', 'Imprevisto');
});

it('exige autenticação nas rotas protegidas (401)', function () {
    $this->getJson('/api/me')->assertUnauthorized()->assertJsonPath('message', 'Não autenticado. Faça login novamente.');
    $this->getJson('/api/agendamentos')->assertUnauthorized();
    $this->getJson("{$this->base}/agenda?data=2026-11-17")->assertUnauthorized();
});

it('trata como 404 o acesso ao painel de quem não tem vínculo', function () {
    entrarComo(conta('Só cliente'));

    $this->getJson($this->base)->assertNotFound();
});

it('não deixa um ex-barbeiro acessar o painel', function () {
    entrarComo($this->dono);
    $this->deleteJson("{$this->base}/equipe/{$this->barbeiro->id}")->assertOk()->assertJsonPath('status', 'i');

    entrarComo(loginDo($this->barbeiro));
    $this->getJson("{$this->base}/agenda?data=2026-11-17")->assertNotFound();
});
