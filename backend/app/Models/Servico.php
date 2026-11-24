<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servico extends Modelo
{
    protected $table = 'servico';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'duracao_minutos' => 'integer',
        ];
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    public function scopeAtivos(Builder $query): void
    {
        $query->where('status', 'a');
    }

    public function ativo(): bool
    {
        return $this->status === 'a';
    }
}
