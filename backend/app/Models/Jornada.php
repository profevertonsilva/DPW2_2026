<?php

namespace App\Models;

class Jornada extends Modelo
{
    protected $table = 'jornada';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'dia_semana' => 'integer',
        ];
    }
}
