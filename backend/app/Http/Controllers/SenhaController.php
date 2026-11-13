<?php

namespace App\Http\Controllers;

use App\Mail\CodigoRedefinicaoSenha;
use App\Models\Login;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Recuperação de senha (RF #06, telas 10 e 11). O e-mail leva um código de
 * 6 dígitos válido por 30 minutos (DECISOES 4.14), guardado com hash em
 * password_reset_tokens. Em desenvolvimento, o e-mail vai para storage/logs.
 */
class SenhaController extends Controller
{
    private const MINUTOS = 30;

    private const TENTATIVAS = 5;

    /** Sempre a mesma resposta, exista a conta ou não (não revela e-mails cadastrados). */
    public function solicitar(Request $request): JsonResponse
    {
        $dados = $request->validate(['email' => ['required', 'string', 'email', 'max:150']]);
        $email = mb_strtolower(trim($dados['email']));
        $login = Login::with('cliente')->where('email', $email)->first();

        if ($login && $login->ativo()) {
            $codigo = (string) random_int(100000, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($codigo), 'created_at' => CarbonImmutable::now()],
            );
            Cache::forget($this->chaveTentativas($email));

            Mail::to($email)->send(new CodigoRedefinicaoSenha($login->cliente?->nome ?? $email, $codigo, self::MINUTOS));
        }

        return response()->json([
            'message' => 'Se houver uma conta com este e-mail, enviamos um código para redefinir a senha. Ele vale por 30 minutos.',
        ]);
    }

    public function redefinir(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'codigo' => ['required', 'string', 'digits:6'],
            // Mesmas regras do cadastro (RF #04).
            'senha' => ['required', 'string', 'max:50', Password::min(8)->symbols()],
        ]);
        $email = mb_strtolower(trim($dados['email']));
        $registro = DB::table('password_reset_tokens')->where('email', $email)->first();
        $invalido = ValidationException::withMessages(['codigo' => 'Código inválido ou expirado. Peça um novo código.']);

        if (! $registro || CarbonImmutable::parse($registro->created_at)->addMinutes(self::MINUTOS)->isPast()) {
            throw $invalido;
        }

        if (! Hash::check($dados['codigo'], $registro->token)) {
            // Depois de várias tentativas erradas, o código deixa de valer.
            $chave = $this->chaveTentativas($email);
            Cache::add($chave, 0, CarbonImmutable::now()->addMinutes(self::MINUTOS));
            if (Cache::increment($chave) >= self::TENTATIVAS) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            }
            throw $invalido;
        }

        $login = Login::where('email', $email)->firstOrFail();

        DB::transaction(function () use ($login, $dados, $email) {
            $login->update(['senha_hash' => $dados['senha']]);
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            // Sessões abertas com a senha antiga são encerradas.
            $login->tokens()->delete();
        });
        Cache::forget($this->chaveTentativas($email));

        return response()->json(['message' => 'Senha redefinida. Entre com a nova senha.']);
    }

    private function chaveTentativas(string $email): string
    {
        return 'redefinir-senha:'.sha1($email);
    }
}
