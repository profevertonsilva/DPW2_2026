<?php

namespace App\Models;

class HorarioFuncionamento extends Modelo
{
    protected $table = 'horario_funcionamento';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
            'aberto' => 'boolean',
        ];
    }
}
