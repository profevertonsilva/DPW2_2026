<?php

namespace App\Http\Controllers\Painel;

use App\Dominio\Agendamentos;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Atendimento;
use App\Models\Estabelecimento;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Agenda do dia e ações sobre agendamentos no painel (RF #21, #23, #33).
 * Proprietário, gerente e atendente: toda a barbearia. Barbeiro: apenas os próprios atendimentos.
 */
class AgendaController extends Controller
{
    public function __construct(private readonly Agendamentos $agendamentos) {}

    public function index(Request $request, Estabelecimento $estabelecimento): AnonymousResourceCollection
    {
        $dados = $request->validate([
            'data' => ['required', 'date_format:Y-m-d'],
            'vinculo_id' => ['nullable', 'integer'],
        ]);

        $vinculo = $this->vinculo($request);
        $dia = CarbonImmutable::createFromFormat('Y-m-d', $dados['data'], config('app.timezone'))->startOfDay();

        // O barbeiro sempre vê só a própria agenda; quem gere a agenda pode filtrar.
        $filtro = $vinculo->gereAgenda() ? ($dados['vinculo_id'] ?? null) : $vinculo->id;

        $atendimentos = $estabelecimento->atendimentos()
            ->with(AgendamentoResource::RELACOES)
            ->where('inicio', '>=', $dia)
            ->where('inicio', '<', $dia->addDay())
            ->when($filtro, fn ($q) => $q->where('vinculo_estabelecimento_id', $filtro))
            ->orderBy('inicio')
            ->get();

        return AgendamentoResource::collection($atendimentos);
    }

    public function show(Request $request, Estabelecimento $estabelecimento, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirAcesso($request, $atendimento);

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    /** Check-in manual: marca o atendimento como realizado (RF #23, sem QR Code). */
    public function realizado(Request $request, Estabelecimento $estabelecimento, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirAcesso($request, $atendimento);

        $this->agendamentos->marcarRealizado($atendimento);

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    public function cancelar(Request $request, Estabelecimento $estabelecimento, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirAcesso($request, $atendimento);
        $dados = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        $this->agendamentos->cancelar($atendimento, $dados['motivo'] ?? null);

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    private function vinculo(Request $request): VinculoEstabelecimento
    {
        return $request->attributes->get('vinculo');
    }

    /** O barbeiro só age sobre os próprios atendimentos. */
    private function exigirAcesso(Request $request, Atendimento $atendimento): void
    {
        $vinculo = $this->vinculo($request);

        abort_if(
            ! $vinculo->gereAgenda() && (int) $atendimento->vinculo_estabelecimento_id !== (int) $vinculo->id,
            403,
            'Este agendamento é de outro barbeiro.',
        );
    }
}
