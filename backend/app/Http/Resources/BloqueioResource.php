<?php

namespace App\Http\Resources;

use App\Support\Datas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Bloqueio de horário de um barbeiro (RF #14). */
class BloqueioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vinculo_id' => $this->vinculo_estabelecimento_id,
            'barbeiro' => $this->vinculo?->profissional?->nome,
            'inicio' => Datas::iso($this->inicio),
            'fim' => Datas::iso($this->fim),
            'data_inicio' => Datas::data($this->inicio),
            'hora_inicio' => Datas::hora($this->inicio),
            'data_fim' => Datas::data($this->fim),
            'hora_fim' => Datas::hora($this->fim),
            'motivo' => $this->motivo,
        ];
    }
}
