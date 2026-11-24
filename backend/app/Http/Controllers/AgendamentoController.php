<?php

namespace App\Http\Controllers;

use App\Dominio\Agendamentos;
use App\Dominio\Disponibilidade;
use App\Http\Resources\AgendamentoResource;
use App\Models\Atendimento;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

/** Agendamentos do cliente: criar, listar, ver, cancelar e remarcar (RF #18, #21, #33). */
class AgendamentoController extends Controller
{
    public function __construct(private readonly Agendamentos $agendamentos) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $dados = $request->validate(['periodo' => ['nullable', 'in:proximos,historico']]);
        $agora = CarbonImmutable::now();

        $query = Atendimento::query()
            ->with(AgendamentoResource::RELACOES)
            ->where('cliente_id', $this->cliente($request)->id);

        if (($dados['periodo'] ?? 'proximos') === 'proximos') {
            $query->where('status', Atendimento::AGENDADO)->where('inicio', '>', $agora)->orderBy('inicio');
        } else {
            $query->where(fn ($q) => $q->where('status', '<>', Atendimento::AGENDADO)->orWhere('inicio', '<=', $agora))
                ->orderByDesc('inicio');
        }

        return AgendamentoResource::collection($query->limit(100)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'servico_id' => ['required', 'integer'],
            'vinculo_id' => ['required', 'integer'],
            'inicio' => ['required', 'date'],
        ]);

        $vinculo = VinculoEstabelecimento::with('estabelecimento')->find($dados['vinculo_id']);
        $servico = Servico::find($dados['servico_id']);

        if (! $vinculo) {
            throw ValidationException::withMessages(['vinculo_id' => 'Barbeiro não encontrado.']);
        }
        if (! $servico) {
            throw ValidationException::withMessages(['servico_id' => 'Serviço não encontrado.']);
        }

        $atendimento = $this->agendamentos->criar(
            $this->cliente($request),
            $vinculo,
            $servico,
            $this->instante($dados['inicio']),
        );

        return (new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES)))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirDono($request, $atendimento);

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    public function cancelar(Request $request, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirDono($request, $atendimento);
        $dados = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        $this->agendamentos->cancelarPeloCliente($atendimento, $dados['motivo'] ?? null);

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    public function remarcar(Request $request, Atendimento $atendimento): AgendamentoResource
    {
        $this->exigirDono($request, $atendimento);
        $dados = $request->validate(['inicio' => ['required', 'date']]);

        $atendimento = $this->agendamentos->remarcar($atendimento, $this->instante($dados['inicio']));

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    /** Grade para a remarcação: mesmo barbeiro e duração, ignorando o próprio atendimento. */
    public function disponibilidade(Request $request, Atendimento $atendimento, Disponibilidade $disponibilidade): JsonResponse
    {
        $this->exigirDono($request, $atendimento);
        $dados = $request->validate(['data' => ['required', 'date_format:Y-m-d']]);

        $data = CarbonImmutable::createFromFormat('Y-m-d', $dados['data'], config('app.timezone'))->startOfDay();
        $duracao = (int) $atendimento->inicio->diffInMinutes($atendimento->fim);
        $vinculo = $atendimento->vinculo;

        $horarios = $vinculo->ativo() ? $disponibilidade->horarios($vinculo, $duracao, $data, $atendimento->id) : [];

        return response()->json(BarbeariaController::grade($dados['data'], $horarios));
    }

    private function cliente(Request $request): Cliente
    {
        $cliente = $request->user()->cliente;
        abort_if($cliente === null, 403, 'Conta sem perfil de cliente.');

        return $cliente;
    }

    private function exigirDono(Request $request, Atendimento $atendimento): void
    {
        abort_if(
            (int) $atendimento->cliente_id !== (int) $this->cliente($request)->id,
            403,
            'Este agendamento pertence a outro cliente.',
        );
    }

    /** Converte o instante recebido (ISO 8601) para o fuso da aplicação. */
    private function instante(string $valor): CarbonImmutable
    {
        return CarbonImmutable::parse($valor)->setTimezone(config('app.timezone'))->startOfMinute();
    }
}
