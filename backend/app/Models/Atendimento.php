<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Agendamento/atendimento. Ciclo no MVP: 'agendado' -> 'realizado' ou 'cancelado'.
 */
class Atendimento extends Modelo
{
    protected $table = 'atendimento';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    const AGENDADO = 'agendado';

    const REALIZADO = 'realizado';

    const CANCELADO = 'cancelado';

    /** Status que ocupam o horário (os mesmos da restrição de exclusão). */
    const STATUS_ATIVOS = ['reservado', 'agendado', 'aguardando_confirmacao', 'confirmado', 'realizado'];

    protected function casts(): array
    {
        return [
            'inicio' => 'immutable_datetime',
            'fim' => 'immutable_datetime',
            'reserva_expira_em' => 'immutable_datetime',
            'presenca_confirmada_em' => 'immutable_datetime',
            'cancelada_em' => 'immutable_datetime',
            'valor_final' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    public function vinculo(): BelongsTo
    {
        return $this->belongsTo(VinculoEstabelecimento::class, 'vinculo_estabelecimento_id');
    }

    /** Serviços do atendimento, com o preço praticado (servico_atendimento.valor). */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'servico_atendimento', 'atendimento_id', 'servico_id')
            ->withPivot('valor');
    }

    public function avaliacao(): HasOne
    {
        return $this->hasOne(Avaliacao::class, 'atendimento_id');
    }

    public function scopeAtivos(Builder $query): void
    {
        $query->whereIn('status', self::STATUS_ATIVOS);
    }
}
