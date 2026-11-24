<?php

use App\Mail\CodigoRedefinicaoSenha;
use App\Models\Login;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/*
| RF #06: recuperação de senha (desejável 3, telas 10 e 11).
*/

beforeEach(function () {
    Mail::fake();
    $this->login = conta('Bia Souza', 'bia@teste.test');
});

/** Pede o código e devolve o que foi "enviado" por e-mail. */
function pedirCodigo(string $email = 'bia@teste.test'): ?string
{
    test()->postJson('/api/auth/esqueci-senha', ['email' => $email])->assertOk();
    $codigo = null;
    Mail::assertSent(CodigoRedefinicaoSenha::class, function ($mail) use (&$codigo) {
        $codigo = $mail->codigo;

        return true;
    });

    return $codigo;
}

it('envia um código de 6 dígitos para o e-mail cadastrado', function () {
    $this->postJson('/api/auth/esqueci-senha', ['email' => 'BIA@teste.test'])
        ->assertOk()
        ->assertJsonPath('message', 'Se houver uma conta com este e-mail, enviamos um código para redefinir a senha. Ele vale por 30 minutos.');

    Mail::assertSent(CodigoRedefinicaoSenha::class, fn ($m) => $m->hasTo('bia@teste.test')
        && preg_match('/^\d{6}$/', $m->codigo)
        && $m->nome === 'Bia Souza');
});

it('responde igual para e-mail sem conta, sem enviar nada', function () {
    $this->postJson('/api/auth/esqueci-senha', ['email' => 'ninguem@teste.test'])
        ->assertOk()->assertJsonPath('message', 'Se houver uma conta com este e-mail, enviamos um código para redefinir a senha. Ele vale por 30 minutos.');

    Mail::assertNothingSent();
});

it('redefine a senha com o código, encerra as sessões e invalida o código', function () {
    $tokenAntigo = $this->login->createToken('app')->plainTextToken;
    $codigo = pedirCodigo();

    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $codigo, 'senha' => 'Nova@1234'])
        ->assertOk()->assertJsonPath('message', 'Senha redefinida. Entre com a nova senha.');

    expect(Hash::check('Nova@1234', $this->login->refresh()->senha_hash))->toBeTrue()
        ->and($this->login->tokens()->count())->toBe(0);

    $this->app['auth']->forgetGuards();
    $this->withToken($tokenAntigo)->getJson('/api/me')->assertUnauthorized();
    $this->postJson('/api/auth/login', ['email' => 'bia@teste.test', 'senha' => 'Nova@1234'])->assertOk();

    // O mesmo código não serve duas vezes.
    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $codigo, 'senha' => 'Outra@1234'])
        ->assertUnprocessable()->assertJsonPath('errors.codigo.0', 'Código inválido ou expirado. Peça um novo código.');
});

it('recusa código errado ou expirado (30 minutos)', function () {
    $codigo = pedirCodigo();
    $errado = $codigo === '111111' ? '222222' : '111111';

    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $errado, 'senha' => 'Nova@1234'])
        ->assertUnprocessable()->assertJsonValidationErrors(['codigo']);

    $this->travel(31)->minutes();
    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $codigo, 'senha' => 'Nova@1234'])
        ->assertUnprocessable()->assertJsonPath('errors.codigo.0', 'Código inválido ou expirado. Peça um novo código.');

    expect(Hash::check('Senha@123', Login::first()->senha_hash))->toBeTrue();
});

it('invalida o código depois de 5 tentativas erradas', function () {
    $codigo = pedirCodigo();
    $errado = $codigo === '111111' ? '222222' : '111111';

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $errado, 'senha' => 'Nova@1234'])->assertUnprocessable();
    }

    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $codigo, 'senha' => 'Nova@1234'])
        ->assertUnprocessable()->assertJsonValidationErrors(['codigo']);
});

it('aplica à nova senha as mesmas regras do cadastro (RF #04)', function () {
    $codigo = pedirCodigo();

    $this->postJson('/api/auth/redefinir-senha', ['email' => 'bia@teste.test', 'codigo' => $codigo, 'senha' => 'semespecial1'])
        ->assertUnprocessable()->assertJsonPath('errors.senha.0', 'O campo senha deve conter pelo menos um caractere especial.');
});
