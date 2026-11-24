<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliação de um atendimento realizado (RF #30). Uma por atendimento,
 * editável em até 7 dias após o envio. status 'a' = visível (pendência 5.10).
 */
class Avaliacao extends Modelo
{
    protected $table = 'avaliacao';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    const DIAS_PARA_EDITAR = 7;

    protected function casts(): array
    {
        return [
            'nota' => 'integer',
            'criado_em' => 'immutable_datetime',
            'atualizado_em' => 'immutable_datetime',
        ];
    }

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class, 'atendimento_id');
    }

    public function editavelAte(): CarbonImmutable
    {
        return $this->criado_em->addDays(self::DIAS_PARA_EDITAR);
    }

    public function editavel(): bool
    {
        return CarbonImmutable::now()->lessThanOrEqualTo($this->editavelAte());
    }
}
