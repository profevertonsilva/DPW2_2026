<?php

namespace App\Http\Resources;

use App\Models\VinculoEstabelecimento;
use App\Support\Datas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Barbearia. Na listagem da busca, só os dados básicos; com
 * `completo()`, também horário, serviços ativos e barbeiros (RF #15).
 */
class BarbeariaResource extends JsonResource
{
    private bool $completo = false;

    public function completo(): static
    {
        $this->completo = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $dados = [
            'id' => $this->id,
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'telefone' => $this->telefones->first()?->telefone,
            'endereco' => [
                'cep' => $this->cep,
                'estado' => $this->estado,
                'cidade' => $this->cidade,
                'bairro' => $this->bairro,
                'logradouro' => $this->logradouro,
                'numero' => $this->numero,
                'complemento' => $this->complemento,
            ],
            // RF #30: média das avaliações visíveis (carregada com scopeComAvaliacao).
            'avaliacao' => [
                'media' => $this->avaliacoes_avg_nota !== null ? round((float) $this->avaliacoes_avg_nota, 1) : null,
                'total' => (int) ($this->avaliacoes_count ?? 0),
            ],
        ];

        if (! $this->completo) {
            return $dados;
        }

        $servicosAtivos = $this->servicos->where('status', 'a')->sortBy('nome')->values();
        $idsAtivos = $servicosAtivos->pluck('id')->all();

        return [
            ...$dados,
            'horarios' => $this->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana,
                'aberto' => $h->aberto,
                'hora_abertura' => Datas::horaCurta($h->hora_abertura),
                'hora_fechamento' => Datas::horaCurta($h->hora_fechamento),
            ])->values(),
            'servicos' => ServicoResource::collection($servicosAtivos),
            // Quem atende: vínculo ativo que executa ao menos um serviço ativo (seção 6.2).
            'barbeiros' => $this->vinculos
                ->where('status', 'a')
                ->map(fn (VinculoEstabelecimento $v) => [
                    'vinculo_id' => $v->id,
                    'nome' => $v->profissional->nome,
                    'servico_ids' => $v->servicos->pluck('id')->intersect($idsAtivos)->values(),
                ])
                ->filter(fn (array $b) => $b['servico_ids']->isNotEmpty())
                ->sortBy('nome')
                ->values(),
        ];
    }
}
