<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Conta de acesso: a entidade autenticável do AgendAki (e-mail e senha).
 * Nome e telefone ficam em `cliente` (e em `profissional`, quando existir).
 */
class Login extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'login';

    const CREATED_AT = 'data_criacao';

    const UPDATED_AT = 'data_atualizacao';

    protected $dateFormat = 'Y-m-d H:i:sP';

    protected $authPasswordName = 'senha_hash';

    protected $rememberTokenName = null;

    protected $guarded = [];

    protected $hidden = ['senha_hash'];

    protected function casts(): array
    {
        return [
            'senha_hash' => 'hashed',
            'ultimo_acesso' => 'immutable_datetime',
        ];
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'login_id');
    }

    public function profissional(): HasOne
    {
        return $this->hasOne(Profissional::class, 'login_id');
    }

    public function ativo(): bool
    {
        return $this->status === 'a';
    }
}
