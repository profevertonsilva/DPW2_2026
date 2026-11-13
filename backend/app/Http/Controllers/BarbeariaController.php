<?php

namespace App\Http\Controllers;

use App\Dominio\Disponibilidade;
use App\Http\Resources\BarbeariaResource;
use App\Models\Estabelecimento;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use App\Support\Busca;
use App\Support\Datas;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** Rotas públicas do cliente: busca, página da barbearia e grade de horários. */
class BarbeariaController extends Controller
{
    /** Busca por nome e cidade (RF #17, simplificado). */
    public function index(Request $request): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'busca' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'string', 'max:50'],
        ]);

        $barbearias = Estabelecimento::ativos()->comAvaliacao()
            ->with('telefones')
            ->when($dados['busca'] ?? null, fn ($q, $busca) => Busca::contem($q, 'nome', $busca))
            ->when($dados['cidade'] ?? null, fn ($q, $cidade) => Busca::contem($q, 'cidade', $cidade))
            ->orderBy('nome')
            ->limit(50)
            ->get();

        return BarbeariaResource::collection($barbearias);
    }

    /** Dados, horário, serviços ativos e barbeiros com os serviços que executam (RF #15). */
    public function show(Estabelecimento $estabelecimento): BarbeariaResource
    {
        abort_if($estabelecimento->status !== 'a', 404, 'Barbearia não encontrada.');

        $estabelecimento->load(['telefones', 'horarios', 'servicos', 'vinculos.profissional', 'vinculos.servicos'])->carregarAvaliacao();

        return (new BarbeariaResource($estabelecimento))->completo();
    }

    /** Inícios livres para um barbeiro, um serviço e uma data (RF #19). */
    public function disponibilidade(Request $request, Estabelecimento $estabelecimento, Disponibilidade $disponibilidade): JsonResponse
    {
        abort_if($estabelecimento->status !== 'a', 404, 'Barbearia não encontrada.');

        $dados = $request->validate([
            'servico_id' => ['required', 'integer'],
            'vinculo_id' => ['required', 'integer'],
            'data' => ['required', 'date_format:Y-m-d'],
        ]);

        $servico = Servico::ativos()->where('estabelecimento_id', $estabelecimento->id)->find($dados['servico_id']);
        $vinculo = VinculoEstabelecimento::ativos()->where('estabelecimento_id', $estabelecimento->id)->find($dados['vinculo_id']);

        if (! $servico) {
            throw ValidationException::withMessages(['servico_id' => 'Serviço não encontrado nesta barbearia.']);
        }
        if (! $vinculo) {
            throw ValidationException::withMessages(['vinculo_id' => 'Barbeiro não encontrado nesta barbearia.']);
        }
        if (! $vinculo->servicos()->whereKey($servico->id)->exists()) {
            throw ValidationException::withMessages(['servico_id' => 'Este barbeiro não executa o serviço escolhido.']);
        }

        $data = CarbonImmutable::createFromFormat('Y-m-d', $dados['data'], config('app.timezone'))->startOfDay();

        return response()->json(self::grade($dados['data'], $disponibilidade->horarios($vinculo, $servico->duracao_minutos, $data)));
    }

    /** @param list<CarbonImmutable> $horarios */
    public static function grade(string $data, array $horarios): array
    {
        return [
            'data' => $data,
            'horarios' => array_map(fn (CarbonImmutable $h) => [
                'inicio' => Datas::iso($h),
                'hora' => Datas::hora($h),
            ], $horarios),
        ];
    }
}
