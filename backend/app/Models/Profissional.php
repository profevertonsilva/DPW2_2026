<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profissional extends Modelo
{
    protected $table = 'profissional';

    public $timestamps = false;

    public function login(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'login_id');
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(VinculoEstabelecimento::class, 'profissional_id');
    }
}
