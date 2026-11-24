<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueioHorario extends Modelo
{
    protected $table = 'bloqueio_horario';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = null;

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(VinculoEstabelecimento::class, 'vinculo_estabelecimento_id');
    }

    protected function casts(): array
    {
        return [
            'inicio' => 'immutable_datetime',
            'fim' => 'immutable_datetime',
        ];
    }
}
