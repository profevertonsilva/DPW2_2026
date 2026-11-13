<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Estabelecimento extends Modelo
{
    protected $table = 'estabelecimento';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'cobranca_antecipada' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaEstabelecimento::class, 'categoria_estabelecimento_id');
    }

    public function telefones(): HasMany
    {
        return $this->hasMany(Telefone::class, 'estabelecimento_id')->orderBy('id');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioFuncionamento::class, 'estabelecimento_id')->orderBy('dia_semana');
    }

    /** Vínculos (equipe). Também usado pelos scoped bindings de {vinculo}. */
    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoEstabelecimento::class, 'estabelecimento_id');
    }

    /** Usado pelos scoped bindings de {servico}. */
    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class, 'estabelecimento_id');
    }

    /** Usado pelos scoped bindings de {atendimento}. */
    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class, 'estabelecimento_id');
    }

    /** Bloqueios de horário dos vínculos (scoped bindings de {bloqueio}). */
    public function bloqueios(): HasManyThrough
    {
        return $this->hasManyThrough(BloqueioHorario::class, VinculoEstabelecimento::class, 'estabelecimento_id', 'vinculo_estabelecimento_id');
    }

    /** Avaliações dos atendimentos da barbearia (RF #30). */
    public function avaliacoes(): HasManyThrough
    {
        return $this->hasManyThrough(Avaliacao::class, Atendimento::class, 'estabelecimento_id', 'atendimento_id');
    }

    /** Média e total das avaliações visíveis (avaliacoes_avg_nota, avaliacoes_count). */
    public function scopeComAvaliacao(Builder $query): void
    {
        $visiveis = fn ($q) => $q->where('avaliacao.status', 'a');
        $query->withAvg(['avaliacoes' => $visiveis], 'nota')->withCount(['avaliacoes' => $visiveis]);
    }

    /** Mesmo que scopeComAvaliacao, para um modelo já carregado. */
    public function carregarAvaliacao(): static
    {
        $visiveis = fn ($q) => $q->where('avaliacao.status', 'a');

        return $this->loadAvg(['avaliacoes' => $visiveis], 'nota')->loadCount(['avaliacoes' => $visiveis]);
    }

    public function scopeAtivos(Builder $query): void
    {
        $query->where('status', 'a');
    }
}
