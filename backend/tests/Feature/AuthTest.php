<?php

use App\Models\Login;
use Illuminate\Support\Facades\Hash;

/*
| RF #04, #05, #08: cadastro, login e logout.
*/

function cadastrar(array $dados = [])
{
    return test()->postJson('/api/auth/cadastro', [
        'nome' => 'Ana Souza',
        'email' => 'Ana@Exemplo.test',
        'telefone' => '(19) 99999-1234',
        'senha' => 'Senha@123',
        ...$dados,
    ]);
}

it('cadastra login e cliente e devolve o token', function () {
    $resposta = cadastrar()
        ->assertCreated()
        ->assertJsonStructure(['token', 'usuario' => ['id', 'email', 'nome', 'telefone', 'cliente_id', 'barbearias']])
        ->assertJsonPath('usuario.email', 'ana@exemplo.test')
        ->assertJsonPath('usuario.nome', 'Ana Souza')
        ->assertJsonPath('usuario.barbearias', []);

    $login = Login::where('email', 'ana@exemplo.test')->sole();
    expect($login->tipo_usuario)->toBe('cliente')
        ->and($login->status)->toBe('a')
        ->and(Hash::check('Senha@123', $login->senha_hash))->toBeTrue()
        ->and($login->cliente->email)->toBe('ana@exemplo.test')
        ->and($login->profissional)->toBeNull();

    $this->withToken($resposta->json('token'))->getJson('/api/me')->assertOk()->assertJsonPath('nome', 'Ana Souza');
});

it('recusa e-mail repetido, sem diferenciar maiúsculas', function () {
    cadastrar()->assertCreated();

    cadastrar(['email' => 'ANA@exemplo.test'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Este e-mail já está em uso.');
});

it('valida os campos do cadastro (RF #04)', function () {
    cadastrar(['nome' => 'Al', 'senha' => 'semespecial1', 'telefone' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nome', 'senha', 'telefone'])
        ->assertJsonPath('errors.senha.0', 'O campo senha deve conter pelo menos um caractere especial.');

    cadastrar(['senha' => 'Ab@1'])->assertUnprocessable()->assertJsonValidationErrors(['senha']);
});

it('faz login com e-mail e senha e atualiza o último acesso', function () {
    $login = conta('Bia', 'bia@teste.test');

    $this->postJson('/api/auth/login', ['email' => 'BIA@teste.test', 'senha' => 'Senha@123'])
        ->assertOk()
        ->assertJsonStructure(['token', 'usuario'])
        ->assertJsonPath('usuario.nome', 'Bia');

    expect($login->refresh()->ultimo_acesso)->not->toBeNull();
});

it('responde com mensagem genérica quando a senha ou o e-mail estão errados', function () {
    conta('Bia', 'bia@teste.test');

    $this->postJson('/api/auth/login', ['email' => 'bia@teste.test', 'senha' => 'errada'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'E-mail ou senha inválidos.');
    $this->postJson('/api/auth/login', ['email' => 'ninguem@teste.test', 'senha' => 'Senha@123'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'E-mail ou senha inválidos.');
});

it('não deixa entrar um login inativo', function () {
    conta('Bia', 'bia@teste.test')->update(['status' => 'i']);

    $this->postJson('/api/auth/login', ['email' => 'bia@teste.test', 'senha' => 'Senha@123'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Esta conta está inativa.');
});

it('edita nome e telefone do perfil, no cliente e no profissional (RF #07)', function () {
    $login = entrarComo(conta('Bia', 'bia@teste.test'));
    barbearia($login); // cria o profissional com os dados antigos

    $this->putJson('/api/me', ['nome' => 'Beatriz Lima', 'telefone' => '(19) 98888-1234'])
        ->assertOk()
        ->assertJsonPath('nome', 'Beatriz Lima')
        ->assertJsonPath('telefone', '(19) 98888-1234')
        ->assertJsonPath('email', 'bia@teste.test');

    $login->refresh();
    expect($login->cliente->nome)->toBe('Beatriz Lima')
        ->and($login->profissional->nome)->toBe('Beatriz Lima')
        ->and($login->profissional->telefone)->toBe('(19) 98888-1234');
});

it('valida a edição de perfil', function () {
    entrarComo(conta());

    $this->putJson('/api/me', ['nome' => 'Al', 'telefone' => 'abc'])
        ->assertUnprocessable()->assertJsonValidationErrors(['nome', 'telefone']);
});

it('revoga apenas o token atual no logout', function () {
    $login = conta();
    $token1 = $login->createToken('app')->plainTextToken;
    $token2 = $login->createToken('painel')->plainTextToken;

    $this->withToken($token1)->postJson('/api/auth/logout')->assertOk();

    expect($login->tokens()->count())->toBe(1);
    $this->app['auth']->forgetGuards();
    $this->withToken($token1)->getJson('/api/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->withToken($token2)->getJson('/api/me')->assertOk();
});
