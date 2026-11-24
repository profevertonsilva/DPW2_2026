<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Registro da trilha de auditoria (RF #38). Não editável. */
class Auditoria extends Modelo
{
    protected $table = 'auditoria';

    const CREATED_AT = 'data_hora';

    const UPDATED_AT = null;

    const CANCELAMENTO = 'cancelamento';

    const REMARCACAO = 'remarcacao';

    const ALTERACAO_CARGO = 'alteracao_cargo';

    const REMOCAO_COLABORADOR = 'remocao_colaborador';

    const ALTERACAO_ESTABELECIMENTO = 'alteracao_estabelecimento';

    protected function casts(): array
    {
        return [
            'valor_anterior' => 'array',
            'valor_novo' => 'array',
            'data_hora' => 'immutable_datetime',
        ];
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'usuario_responsavel');
    }
}
