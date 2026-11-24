<?php

use App\Models\Estabelecimento;
use App\Models\HorarioFuncionamento;
use App\Models\VinculoEstabelecimento;

/*
| RF #09: cadastro da barbearia (seção 6.2 e 9.1).
*/

it('cria a barbearia com vínculo de proprietário e os sete horários', function () {
    $login = entrarComo(conta('Carla Dona'));

    $resposta = $this->postJson('/api/painel/estabelecimentos', dadosBarbearia([
        'nome' => 'Barbearia Nova',
        'horarios' => semana([0 => null, 6 => ['08:00', '12:00']]),
    ]))->assertCreated()
        ->assertJsonPath('nome', 'Barbearia Nova')
        ->assertJsonPath('papel', 'proprietario')
        ->assertJsonCount(7, 'horarios')
        ->assertJsonPath('horarios.0', ['dia_semana' => 0, 'aberto' => false, 'hora_abertura' => null, 'hora_fechamento' => null])
        ->assertJsonPath('horarios.6.hora_fechamento', '12:00');

    $estabelecimento = Estabelecimento::findOrFail($resposta->json('id'));
    $profissional = $login->refresh()->profissional;

    expect(HorarioFuncionamento::where('estabelecimento_id', $estabelecimento->id)->count())->toBe(7)
        ->and($estabelecimento->categoria->nome)->toBe('Barbearia')
        ->and($estabelecimento->telefones()->value('telefone'))->toBe('(19) 3600-0000')
        // O profissional é criado com os dados do cliente.
        ->and($profissional->nome)->toBe('Carla Dona')
        ->and($profissional->email)->toBe($login->email);

    $vinculo = VinculoEstabelecimento::where('estabelecimento_id', $estabelecimento->id)->sole();
    expect($vinculo->papel)->toBe('proprietario')
        ->and($vinculo->status)->toBe('a')
        ->and($vinculo->profissional_id)->toBe($profissional->id);

    $this->getJson('/api/me')->assertJsonPath('barbearias.0.papel', 'proprietario');
});

it('exige os campos obrigatórios do RF #09', function () {
    entrarComo(conta());

    $this->postJson('/api/painel/estabelecimentos', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nome', 'telefone', 'cep', 'estado', 'cidade', 'bairro', 'logradouro', 'numero', 'horarios'])
        ->assertJsonPath('errors.nome.0', 'O campo nome é obrigatório.');
});

it('exige o horário dos sete dias, sem repetir dia', function () {
    entrarComo(conta());
    $seis = array_slice(semana(), 0, 6);
    $repetido = [...$seis, ['dia_semana' => 1, 'aberto' => false]];

    $this->postJson('/api/painel/estabelecimentos', dadosBarbearia(['horarios' => $seis]))
        ->assertUnprocessable()->assertJsonValidationErrors(['horarios']);
    $this->postJson('/api/painel/estabelecimentos', dadosBarbearia(['horarios' => $repetido]))
        ->assertUnprocessable()->assertJsonValidationErrors(['horarios.1.dia_semana']);
});

it('recusa fechamento antes da abertura e dia aberto sem horário', function () {
    entrarComo(conta());

    $this->postJson('/api/painel/estabelecimentos', dadosBarbearia(['horarios' => semana([2 => ['18:00', '09:00']])]))
        ->assertUnprocessable()->assertJsonValidationErrors(['horarios.2.hora_fechamento']);

    $horarios = semana();
    $horarios[3]['hora_abertura'] = null;
    $this->postJson('/api/painel/estabelecimentos', dadosBarbearia(['horarios' => $horarios]))
        ->assertUnprocessable()->assertJsonValidationErrors(['horarios.3.hora_abertura']);
});

it('reaproveita o profissional de quem já é barbeiro em outra barbearia', function () {
    $login = conta();
    barbeiro(barbearia(), login: $login);
    entrarComo($login);

    $this->postJson('/api/painel/estabelecimentos', dadosBarbearia())->assertCreated();

    expect($login->refresh()->profissional->vinculos()->count())->toBe(2);
    $this->getJson('/api/painel/estabelecimentos')->assertJsonCount(2);
});

it('permite ao proprietário editar dados e horários', function () {
    $dono = entrarComo(conta());
    $barbearia = barbearia($dono);

    $this->putJson("/api/painel/estabelecimentos/{$barbearia->id}", dadosBarbearia([
        'nome' => 'Nome Novo',
        'telefone' => '(19) 98888-7777',
        'horarios' => semana([1 => null]),
    ]))->assertOk()
        ->assertJsonPath('nome', 'Nome Novo')
        ->assertJsonPath('telefone', '(19) 98888-7777')
        ->assertJsonPath('horarios.1.aberto', false);

    expect(HorarioFuncionamento::where('estabelecimento_id', $barbearia->id)->count())->toBe(7);
});
