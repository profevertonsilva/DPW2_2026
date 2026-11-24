<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Modelo
{
    protected $table = 'cliente';

    public $timestamps = false;

    public function login(): BelongsTo
    {
        return $this->belongsTo(Login::class, 'login_id');
    }
}
