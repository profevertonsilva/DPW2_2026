<?php

namespace App\Http\Resources;

use App\Models\VinculoEstabelecimento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Dados do usuário logado e barbearias em que tem vínculo ativo, com o papel. */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cliente = $this->cliente;
        $profissional = $this->profissional;

        $vinculos = $profissional
            ? $profissional->vinculos()->ativos()->with('estabelecimento')->orderBy('id')->get()
            : collect();

        return [
            'id' => $this->id,
            'email' => $this->email,
            'nome' => $cliente?->nome,
            'telefone' => $cliente?->telefone,
            'cliente_id' => $cliente?->id,
            'profissional_id' => $profissional?->id,
            'barbearias' => $vinculos
                ->filter(fn (VinculoEstabelecimento $v) => $v->estabelecimento?->status === 'a')
                ->map(fn (VinculoEstabelecimento $v) => [
                    'id' => $v->estabelecimento_id,
                    'nome' => $v->estabelecimento->nome,
                    'papel' => $v->papel,
                    'vinculo_id' => $v->id,
                ])->values(),
        ];
    }
}
