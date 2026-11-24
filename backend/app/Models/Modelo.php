<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base dos models do AgendAki.
 *
 * As datas são gravadas com o deslocamento ('P'), para que um instante
 * recebido em outro fuso (por exemplo, UTC) não seja reinterpretado como
 * horário de São Paulo ao chegar ao banco.
 */
abstract class Modelo extends Model
{
    protected $dateFormat = 'Y-m-d H:i:sP';

    protected $guarded = [];
}
