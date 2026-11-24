<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Http\Resources\BloqueioResource;
use App\Models\Atendimento;
use App\Models\BloqueioHorario;
use App\Models\Estabelecimento;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/**
 * Bloqueio de horários (RF #14, tela 41). O barbeiro bloqueia a própria agenda;
 * quem gere a agenda bloqueia a de qualquer barbeiro. O bloqueio remove os
 * horários da grade, mas não cancela agendamentos já feitos: a API devolve
 * esses conflitos para que se decida mantê-los ou cancelá-los.
 */
class BloqueioController extends Controller
{
    public function index(Request $request, Estabelecimento $estabelecimento): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'de' => ['required', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'vinculo_id' => ['nullable', 'integer'],
        ]);

        $de = $this->dia($dados['de']);
        $ate = $this->dia($dados['ate'] ?? $dados['de'])->addDay();
        $vinculo = $this->vinculo($request);
        $filtro = $vinculo->gereAgenda() ? ($dados['vinculo_id'] ?? null) : $vinculo->id;

        $bloqueios = $estabelecimento->bloqueios()
            ->with('vinculo.profissional')
            ->where('bloqueio_horario.inicio', '<', $ate)
            ->where('bloqueio_horario.fim', '>', $de)
            ->when($filtro, fn ($q) => $q->where('bloqueio_horario.vinculo_estabelecimento_id', $filtro))
            ->orderBy('bloqueio_horario.inicio')
            ->get();

        return BloqueioResource::collection($bloqueios);
    }

    /** Agendamentos ativos no intervalo, para avisar antes de confirmar o bloqueio. */
    public function conflitos(Request $request, Estabelecimento $estabelecimento): AnonymousResourceCollection
    {
        [$vinculo, $inicio, $fim] = $this->validar($request, $estabelecimento);

        return AgendamentoResource::collection($this->agendamentosNoIntervalo($vinculo, $inicio, $fim));
    }

    public function store(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        [$vinculo, $inicio, $fim] = $this->validar($request, $estabelecimento);
        $motivo = $request->validate(['motivo' => ['nullable', 'string', 'max:500']])['motivo'] ?? null;

        $bloqueio = BloqueioHorario::create([
            'vinculo_estabelecimento_id' => $vinculo->id,
            'inicio' => $inicio,
            'fim' => $fim,
            'motivo' => $motivo,
        ])->load('vinculo.profissional');

        return response()->json([
            'bloqueio' => (new BloqueioResource($bloqueio))->resolve(),
            // RF #14: bloqueios não cancelam automaticamente os agendamentos existentes.
            'conflitos' => AgendamentoResource::collection($this->agendamentosNoIntervalo($vinculo, $inicio, $fim))->resolve(),
        ], 201);
    }

    public function destroy(Request $request, Estabelecimento $estabelecimento, BloqueioHorario $bloqueio): JsonResponse
    {
        $this->exigirAcesso($request, (int) $bloqueio->vinculo_estabelecimento_id);
        $bloqueio->delete();

        return response()->json(['message' => 'Bloqueio removido.']);
    }

    /** @return array{0: VinculoEstabelecimento, 1: CarbonImmutable, 2: CarbonImmutable} */
    private function validar(Request $request, Estabelecimento $estabelecimento): array
    {
        $dados = $request->validate([
            'vinculo_id' => ['required', 'integer'],
            'inicio' => ['required', 'date'],
            'fim' => ['required', 'date'],
        ]);

        $this->exigirAcesso($request, (int) $dados['vinculo_id']);

        $vinculo = VinculoEstabelecimento::ativos()->where('estabelecimento_id', $estabelecimento->id)->find($dados['vinculo_id']);
        if (! $vinculo) {
            throw ValidationException::withMessages(['vinculo_id' => 'Barbeiro não encontrado nesta barbearia.']);
        }

        $inicio = $this->instante($dados['inicio']);
        $fim = $this->instante($dados['fim']);
        if ($fim->lessThanOrEqualTo($inicio)) {
            throw ValidationException::withMessages(['fim' => 'O fim do bloqueio deve ser depois do início.']);
        }

        return [$vinculo, $inicio, $fim];
    }

    private function agendamentosNoIntervalo(VinculoEstabelecimento $vinculo, CarbonImmutable $inicio, CarbonImmutable $fim)
    {
        return Atendimento::ativos()
            ->with(AgendamentoResource::RELACOES)
            ->where('vinculo_estabelecimento_id', $vinculo->id)
            ->where('status', Atendimento::AGENDADO)
            ->where('inicio', '<', $fim)
            ->where('fim', '>', $inicio)
            ->orderBy('inicio')
            ->get();
    }

    /** O barbeiro só bloqueia a própria agenda. */
    private function exigirAcesso(Request $request, int $vinculoId): void
    {
        $vinculo = $this->vinculo($request);
        abort_if(
            ! $vinculo->gereAgenda() && $vinculoId !== (int) $vinculo->id,
            403,
            'Você só pode bloquear a sua própria agenda.',
        );
    }

    private function vinculo(Request $request): VinculoEstabelecimento
    {
        return $request->attributes->get('vinculo');
    }

    private function dia(string $data): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $data, config('app.timezone'))->startOfDay();
    }

    private function instante(string $valor): CarbonImmutable
    {
        return CarbonImmutable::parse($valor)->setTimezone(config('app.timezone'))->startOfMinute();
    }
}
