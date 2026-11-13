<?php

namespace App\Dominio;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

/**
 * Grava a trilha de auditoria (RF #38) com o usuário autenticado como
 * responsável. Sem usuário (seeders, comandos), não registra nada.
 */
class Trilha
{
    public static function registrar(
        int $estabelecimentoId,
        string $tipoOperacao,
        string $entidade,
        int $entidadeAfetada,
        ?array $valorAnterior,
        ?array $valorNovo,
    ): void {
        $usuario = Auth::user();
        if (! $usuario) {
            return;
        }

        Auditoria::create([
            'usuario_responsavel' => $usuario->getAuthIdentifier(),
            'estabelecimento_id' => $estabelecimentoId,
            'tipo_operacao' => $tipoOperacao,
            'entidade' => $entidade,
            'entidade_afetada' => $entidadeAfetada,
            'valor_anterior' => $valorAnterior,
            'valor_novo' => $valorNovo,
        ]);
    }
}
