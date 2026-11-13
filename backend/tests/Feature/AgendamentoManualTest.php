<?php

use App\Models\Atendimento;

/*
| Desejável 5: agendamento manual pelo painel (tela 33), só para cliente cadastrado.
| Hoje é segunda, 16/11/2026, 08:00.
*/

beforeEach(function () {
    $this->dono = conta('Dono');
    $this->barbearia = barbearia($this->dono);
    $this->corte = servico($this->barbearia);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
    $this->colega = barbeiro($this->barbearia, [$this->corte]);
    $this->cliente = conta('Marina Costa', 'marina@teste.test');
    $this->base = "/api/painel/estabelecimentos/{$this->barbearia->id}";
});

function manual(array $sobrescrever = [])
{
    return test()->postJson(test()->base.'/agendamentos', [
        'email_cliente' => 'marina@teste.test',
        'servico_id' => test()->corte->id,
        'vinculo_id' => test()->barbeiro->id,
        'inicio' => sp('2026-11-17 10:00'),
        ...$sobrescrever,
    ]);
}

it('cria agendamento para cliente cadastrado, com a mesma proteção do app', function () {
    entrarComo($this->dono);

    manual()->assertCreated()
        ->assertJsonPath('status', 'agendado')
        ->assertJsonPath('cliente.nome', 'Marina Costa')
        ->assertJsonPath('hora_inicio', '10:00');

    // Mesmo horário de novo: 409 com alternativas (RF #36).
    manual(['email_cliente' => conta('Outra', 'outra@teste.test')->email])
        ->assertStatus(409)->assertJsonStructure(['message', 'alternativas']);

    expect(Atendimento::count())->toBe(1);
});

it('orienta o cadastro quando o e-mail não tem conta', function () {
    entrarComo($this->dono);

    manual(['email_cliente' => 'ninguem@teste.test'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email_cliente.0', 'Nenhum cliente cadastrado com este e-mail. Peça para a pessoa criar a conta no app.');
});

it('deixa o barbeiro agendar só na própria agenda', function () {
    entrarComo(loginDo($this->barbeiro));

    manual()->assertCreated();
    manual(['vinculo_id' => $this->colega->id, 'inicio' => sp('2026-11-17 11:00')])->assertForbidden();
});

it('busca clientes da barbearia por nome e qualquer conta pelo e-mail exato', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-10 10:00', $this->cliente, Atendimento::REALIZADO);
    conta('Mariana Alheia', 'mariana@teste.test'); // nunca agendou aqui
    entrarComo($this->dono);

    $this->getJson("{$this->base}/clientes?busca=mari")
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Marina Costa');

    $this->getJson("{$this->base}/clientes?busca=mariana@teste.test")
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.email', 'mariana@teste.test');
});

it('não aceita serviço nem barbeiro de outra barbearia', function () {
    $outra = barbearia();
    entrarComo($this->dono);

    manual(['servico_id' => servico($outra)->id])->assertUnprocessable()->assertJsonValidationErrors(['servico_id']);
    manual(['vinculo_id' => barbeiro($outra)->id])->assertUnprocessable()->assertJsonValidationErrors(['vinculo_id']);
});
