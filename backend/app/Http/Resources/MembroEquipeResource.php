<?php

namespace App\Http\Resources;

use App\Support\Datas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Vínculo da equipe, com serviços que executa e jornada (telas 37 e 40). */
class MembroEquipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'vinculo_id' => $this->id,
            'papel' => $this->papel,
            'status' => $this->status,
            'vinculado_em' => Datas::iso($this->vinculado_em),
            'nome' => $this->profissional->nome,
            'email' => $this->profissional->email,
            'telefone' => $this->profissional->telefone,
            'servico_ids' => $this->servicos->pluck('id')->values(),
            'jornada' => $this->jornadas->map(fn ($j) => [
                'dia_semana' => $j->dia_semana,
                'hora_inicio' => Datas::horaCurta($j->hora_inicio),
                'hora_fim' => Datas::horaCurta($j->hora_fim),
            ])->values(),
        ];
    }
}
