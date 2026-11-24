<?php

use App\Models\VinculoEstabelecimento;

/*
| RF #10, #11, #13, #15, #17: serviços, equipe, jornada, página e busca.
*/

beforeEach(function () {
    $this->dono = entrarComo(conta('Dono'));
    $this->barbearia = barbearia($this->dono, ['nome' => 'Barbearia Central']);
    $this->base = "/api/painel/estabelecimentos/{$this->barbearia->id}";
});

it('cadastra, edita, lista e inativa serviços', function () {
    $id = $this->postJson("{$this->base}/servicos", ['nome' => 'Corte', 'preco' => 40.5, 'duracao_minutos' => 30])
        ->assertCreated()->assertJsonPath('preco', 40.5)->assertJsonPath('status', 'a')->json('id');

    $this->putJson("{$this->base}/servicos/{$id}", ['nome' => 'Corte social', 'preco' => 45, 'duracao_minutos' => 40])
        ->assertOk()->assertJsonPath('nome', 'Corte social')->assertJsonPath('duracao_minutos', 40);

    $this->deleteJson("{$this->base}/servicos/{$id}")->assertOk()->assertJsonPath('status', 'i');

    // Inativo continua no painel, mas some da página pública.
    $this->getJson("{$this->base}/servicos")->assertOk()->assertJsonCount(1);
    $this->getJson("/api/barbearias/{$this->barbearia->id}")->assertOk()->assertJsonCount(0, 'servicos');

    $this->putJson("{$this->base}/servicos/{$id}", ['nome' => 'Corte social', 'preco' => 45, 'duracao_minutos' => 40, 'status' => 'a'])
        ->assertOk()->assertJsonPath('status', 'a');
});

it('valida preço e duração do serviço (RF #10)', function () {
    $this->postJson("{$this->base}/servicos", ['nome' => 'X', 'preco' => 0, 'duracao_minutos' => 0])
        ->assertUnprocessable()->assertJsonValidationErrors(['preco', 'duracao_minutos']);
});

it('adiciona barbeiro pelo e-mail de quem já tem conta', function () {
    conta('Rafael', 'rafael@teste.test');

    $this->postJson("{$this->base}/equipe", ['email' => 'RAFAEL@teste.test'])
        ->assertCreated()
        ->assertJsonPath('papel', 'profissional')
        ->assertJsonPath('nome', 'Rafael')
        ->assertJsonPath('servico_ids', []);

    $this->getJson("{$this->base}/equipe")->assertOk()->assertJsonCount(2);
});

it('orienta o cadastro quando o e-mail não tem conta e recusa barbeiro repetido', function () {
    $this->postJson("{$this->base}/equipe", ['email' => 'ninguem@teste.test'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Nenhuma conta encontrada com este e-mail. Peça para a pessoa se cadastrar no AgendAki e tente de novo.');

    conta('Rafael', 'rafael@teste.test');
    $this->postJson("{$this->base}/equipe", ['email' => 'rafael@teste.test'])->assertCreated();
    $this->postJson("{$this->base}/equipe", ['email' => 'rafael@teste.test'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Esta pessoa já faz parte da equipe.');
});

it('remove barbeiro encerrando o vínculo, mas não o do proprietário', function () {
    $barbeiro = barbeiro($this->barbearia);

    $this->deleteJson("{$this->base}/equipe/{$barbeiro->id}")->assertOk()->assertJsonPath('status', 'i');
    expect($barbeiro->refresh()->desvinculado_em)->not->toBeNull();

    $this->deleteJson("{$this->base}/equipe/".vinculoDoDono($this->barbearia)->id)
        ->assertUnprocessable()->assertJsonPath('errors.vinculo.0', 'O vínculo do proprietário não pode ser removido.');

    // Pode voltar depois: novo vínculo, histórico preservado.
    $this->postJson("{$this->base}/equipe", ['email' => loginDo($barbeiro)->email])->assertCreated();
    expect(VinculoEstabelecimento::where('profissional_id', $barbeiro->profissional_id)->count())->toBe(2);
});

it('bloqueia a remoção de barbeiro com agendamentos futuros e lista esses agendamentos', function () {
    $corte = servico($this->barbearia);
    $barbeiro = barbeiro($this->barbearia, [$corte]);
    atendimento($barbeiro, $corte, '2026-11-10 10:00', status: 'realizado');      // passado: não impede
    atendimento($barbeiro, $corte, '2026-11-18 10:00', status: 'cancelado');      // cancelado: não impede
    $futuro2 = atendimento($barbeiro, $corte, '2026-11-20 15:00');
    $futuro1 = atendimento($barbeiro, $corte, '2026-11-17 09:00');

    $this->deleteJson("{$this->base}/equipe/{$barbeiro->id}")
        ->assertUnprocessable()
        ->assertJsonPath('errors.vinculo.0', 'Este barbeiro tem agendamentos futuros. Cancele-os antes de removê-lo da equipe.')
        ->assertJsonCount(2, 'agendamentos')
        ->assertJsonPath('agendamentos.0.id', $futuro1->id)
        ->assertJsonPath('agendamentos.1.id', $futuro2->id);

    expect($barbeiro->refresh()->status)->toBe('a');

    // Depois que o dono cancela os dois, a remoção passa.
    foreach ([$futuro1, $futuro2] as $a) {
        $this->postJson("{$this->base}/agendamentos/{$a->id}/cancelar")->assertOk();
    }
    $this->deleteJson("{$this->base}/equipe/{$barbeiro->id}")->assertOk()->assertJsonPath('status', 'i');
});

it('define os serviços de cada barbeiro, inclusive do proprietário', function () {
    $corte = servico($this->barbearia);
    $barba = servico($this->barbearia, nome: 'Barba');
    $dono = vinculoDoDono($this->barbearia);

    $this->putJson("{$this->base}/equipe/{$dono->id}/servicos", ['servico_ids' => [$corte->id, $barba->id]])
        ->assertOk()->assertJsonPath('servico_ids', [$corte->id, $barba->id]);

    $this->putJson("{$this->base}/equipe/{$dono->id}/servicos", ['servico_ids' => []])
        ->assertUnprocessable()->assertJsonValidationErrors(['servico_ids']);

    // O proprietário que corta aparece entre os barbeiros da página pública.
    $this->getJson("/api/barbearias/{$this->barbearia->id}")
        ->assertJsonCount(1, 'barbeiros')
        ->assertJsonPath('barbeiros.0.nome', 'Dono');
});

it('define a jornada com mais de uma faixa por dia e valida sobreposição', function () {
    $barbeiro = barbeiro($this->barbearia);
    $url = "{$this->base}/equipe/{$barbeiro->id}/jornada";

    $this->putJson($url, ['faixas' => [
        ['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fim' => '12:00'],
        ['dia_semana' => 1, 'hora_inicio' => '13:00', 'hora_fim' => '18:00'],
    ]])->assertOk()->assertJsonCount(2, 'jornada')->assertJsonPath('jornada.1.hora_inicio', '13:00');

    $this->putJson($url, ['faixas' => [
        ['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fim' => '12:00'],
        ['dia_semana' => 1, 'hora_inicio' => '11:00', 'hora_fim' => '18:00'],
    ]])->assertUnprocessable()->assertJsonPath('errors', ['faixas.1.hora_inicio' => ['As faixas do mesmo dia não podem se sobrepor.']]);

    $this->putJson($url, ['faixas' => [['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fim' => '09:00']]])
        ->assertUnprocessable()->assertJsonValidationErrors(['faixas.0.hora_fim']);

    // Lista vazia: volta a valer o horário da barbearia.
    $this->putJson($url, ['faixas' => []])->assertOk()->assertJsonCount(0, 'jornada');
});

it('mostra a página pública com horário, serviços ativos e barbeiros', function () {
    $corte = servico($this->barbearia);
    $barbeiro = barbeiro($this->barbearia, [$corte], conta('Rafael'));
    barbeiro($this->barbearia, [], conta('Sem serviço'));

    $this->getJson("/api/barbearias/{$this->barbearia->id}")
        ->assertOk()
        ->assertJsonPath('nome', 'Barbearia Central')
        ->assertJsonPath('endereco.cidade', 'São João da Boa Vista')
        ->assertJsonCount(7, 'horarios')
        ->assertJsonPath('horarios.1.hora_abertura', '09:00')
        ->assertJsonPath('servicos.0.nome', 'Corte')
        ->assertJsonCount(1, 'barbeiros')
        ->assertJsonPath('barbeiros.0', ['vinculo_id' => $barbeiro->id, 'nome' => 'Rafael', 'servico_ids' => [$corte->id]]);
});

it('busca barbearias por nome e cidade, sem diferenciar maiúsculas', function () {
    barbearia(sobrescrever: ['nome' => 'Barbearia do Porto', 'cidade' => 'Águas da Prata']);
    $inativa = barbearia(sobrescrever: ['nome' => 'Barbearia Fechada']);
    $inativa->update(['status' => 'i']);

    $this->getJson('/api/barbearias')->assertOk()->assertJsonCount(2);
    $this->getJson('/api/barbearias?busca=central')->assertJsonCount(1)->assertJsonPath('0.nome', 'Barbearia Central');
    $this->getJson('/api/barbearias?cidade=águas')->assertJsonCount(1)->assertJsonPath('0.nome', 'Barbearia do Porto');
    $this->getJson('/api/barbearias?busca=porto&cidade=são joão')->assertJsonCount(0);
    $this->getJson('/api/barbearias?busca=%25')->assertJsonCount(0);
    $this->getJson("/api/barbearias/{$inativa->id}")->assertNotFound();
});

it('mostra no /me as barbearias e o papel do usuário', function () {
    $this->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('barbearias.0.nome', 'Barbearia Central')
        ->assertJsonPath('barbearias.0.papel', 'proprietario')
        ->assertJsonPath('barbearias.0.vinculo_id', vinculoDoDono($this->barbearia)->id);
});
