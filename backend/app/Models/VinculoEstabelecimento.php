<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Vínculo de um profissional com uma barbearia, com o papel (seção 5.4).
 * Papéis: 'proprietario', 'gerente', 'atendente' e 'profissional' (barbeiro). RF #03.
 */
class VinculoEstabelecimento extends Modelo
{
    protected $table = 'vinculo_estabelecimento';

    const CREATED_AT = 'vinculado_em';

    const UPDATED_AT = null;

    const PROPRIETARIO = 'proprietario';

    const PROFISSIONAL = 'profissional';

    const GERENTE = 'gerente';

    const ATENDENTE = 'atendente';

    protected function casts(): array
    {
        return [
            'vinculado_em' => 'immutable_datetime',
            'desvinculado_em' => 'immutable_datetime',
        ];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_id');
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class, 'estabelecimento_id');
    }

    /** Serviços que o barbeiro executa (tabela vinculo_servico). */
    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'vinculo_servico', 'vinculo_estabelecimento_id', 'servico_id');
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(Jornada::class, 'vinculo_estabelecimento_id')
            ->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class, 'vinculo_estabelecimento_id');
    }

    public function scopeAtivos(Builder $query): void
    {
        $query->where('status', 'a');
    }

    public function ativo(): bool
    {
        return $this->status === 'a';
    }

    public function proprietario(): bool
    {
        return $this->papel === self::PROPRIETARIO;
    }

    /**
     * Vê e gerencia a agenda de toda a barbearia (todos os papéis, menos o
     * barbeiro, que só vê a própria). RF #03.
     */
    public function gereAgenda(): bool
    {
        return $this->papel !== self::PROFISSIONAL;
    }
}
