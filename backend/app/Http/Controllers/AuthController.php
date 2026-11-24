<?php

namespace App\Http\Controllers;

use App\Http\Resources\UsuarioResource;
use App\Models\Login;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** Cadastro, login, logout e dados do usuário (RF #04, #05, #08). */
class AuthController extends Controller
{
    /** Cria login e cliente e devolve o token. Todo usuário é cliente. */
    public function cadastro(Request $request): JsonResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:login,email'],
            'telefone' => ['required', 'string', 'max:15', 'regex:/^[0-9()+\-\s]{8,15}$/'],
            // RF #04: mínimo de 8 caracteres, com pelo menos um caractere especial.
            'senha' => ['required', 'string', 'max:50', Password::min(8)->symbols()],
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ]);

        $login = DB::transaction(function () use ($dados) {
            $login = Login::create([
                'email' => $dados['email'],
                'senha_hash' => $dados['senha'],
                // Pendência 5.10: gravado como 'cliente' e não usado em regras;
                // os papéis vêm do vínculo com a barbearia.
                'tipo_usuario' => 'cliente',
                'status' => 'a',
                'ultimo_acesso' => CarbonImmutable::now(),
            ]);

            $login->cliente()->create([
                'nome' => $dados['nome'],
                'telefone' => $dados['telefone'],
                'email' => $dados['email'],
            ]);

            return $login;
        });

        return $this->respostaComToken($login, $dados['dispositivo'] ?? null, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email'],
            'senha' => ['required', 'string'],
            'dispositivo' => ['nullable', 'string', 'max:100'],
        ]);

        $login = Login::where('email', mb_strtolower(trim($dados['email'])))->first();

        // RF #05: mensagem genérica, sem indicar qual campo está errado.
        if (! $login || ! Hash::check($dados['senha'], $login->senha_hash)) {
            throw ValidationException::withMessages(['email' => 'E-mail ou senha inválidos.']);
        }

        if (! $login->ativo()) {
            throw ValidationException::withMessages(['email' => 'Esta conta está inativa.']);
        }

        $login->forceFill(['ultimo_acesso' => CarbonImmutable::now()])->save();

        return $this->respostaComToken($login, $dados['dispositivo'] ?? null);
    }

    /** Revoga apenas o token usado nesta requisição (RF #08). */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user());
    }

    /**
     * Edição de perfil (RF #07): nome e telefone. Atualiza o cliente e, se
     * existir, o profissional, para o nome ser o mesmo no app e no painel.
     */
    public function atualizarPerfil(Request $request): UsuarioResource
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'telefone' => ['required', 'string', 'max:15', 'regex:/^[0-9()+\-\s]{8,15}$/'],
        ]);
        $login = $request->user();

        DB::transaction(function () use ($login, $dados) {
            $login->cliente?->update($dados);
            $login->profissional?->update($dados);
        });

        return new UsuarioResource($login->refresh());
    }

    private function respostaComToken(Login $login, ?string $dispositivo, int $status = 200): JsonResponse
    {
        $token = $login->createToken($dispositivo ?: 'agendaki')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuario' => (new UsuarioResource($login->fresh()))->resolve(),
        ], $status);
    }
}
