<?php

namespace App\Http\Controllers\Painel;

use App\Dominio\Agendamentos;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgendamentoResource;
use App\Models\Cliente;
use App\Models\Estabelecimento;
use App\Models\Login;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use App\Support\Busca;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Agendamento manual pelo painel (desejável 5, tela 33): para o cliente que
 * chega sem marcar, desde que já tenha cadastro (o banco exige login).
 * Passa pela mesma proteção contra sobreposição do app (RF #36).
 */
class AgendamentoManualController extends Controller
{
    public function __construct(private readonly Agendamentos $agendamentos) {}

    /**
     * Busca de clientes: os que já têm cadastro nesta barbearia (por nome,
     * telefone ou e-mail) e, pelo e-mail exato, qualquer conta da plataforma.
     * Não expõe clientes de outras barbearias por nome (RF #37).
     */
    public function clientes(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $busca = trim($request->validate(['busca' => ['required', 'string', 'min:2', 'max:150']])['busca']);
        $daBarbearia = Cliente::query()
            ->whereIn('id', DB::table('cliente_estabelecimento')->where('estabelecimento_id', $estabelecimento->id)->select('cliente_id'))
            ->where(function ($q) use ($busca) {
                Busca::contem($q, 'nome', $busca);
                Busca::contem($q, 'telefone', $busca, 'or');
                Busca::contem($q, 'email', $busca, 'or');
            })
            ->orderBy('nome')
            ->limit(10)
            ->get();

        $porEmail = Login::with('cliente')->where('email', mb_strtolower($busca))->first()?->cliente;
        $clientes = $daBarbearia->when($porEmail, fn ($c) => $c->prepend($porEmail))->unique('id')->values();

        return response()->json($clientes->map(fn (Cliente $c) => [
            'nome' => $c->nome,
            'email' => $c->login->email,
            'telefone' => $c->telefone,
        ]));
    }

    public function store(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $dados = $request->validate([
            'email_cliente' => ['required', 'string', 'email'],
            'servico_id' => ['required', 'integer'],
            'vinculo_id' => ['required', 'integer'],
            'inicio' => ['required', 'date'],
        ]);

        $meuVinculo = $request->attributes->get('vinculo');
        abort_if(
            ! $meuVinculo->gereAgenda() && (int) $dados['vinculo_id'] !== (int) $meuVinculo->id,
            403,
            'Você só pode agendar na sua própria agenda.',
        );

        $cliente = Login::where('email', mb_strtolower(trim($dados['email_cliente'])))->first()?->cliente;
        if (! $cliente) {
            throw ValidationException::withMessages([
                'email_cliente' => 'Nenhum cliente cadastrado com este e-mail. Peça para a pessoa criar a conta no app.',
            ]);
        }

        $vinculo = VinculoEstabelecimento::with('estabelecimento')
            ->where('estabelecimento_id', $estabelecimento->id)->find($dados['vinculo_id']);
        $servico = Servico::where('estabelecimento_id', $estabelecimento->id)->find($dados['servico_id']);
        if (! $vinculo) {
            throw ValidationException::withMessages(['vinculo_id' => 'Barbeiro não encontrado nesta barbearia.']);
        }
        if (! $servico) {
            throw ValidationException::withMessages(['servico_id' => 'Serviço não encontrado nesta barbearia.']);
        }

        $inicio = CarbonImmutable::parse($dados['inicio'])->setTimezone(config('app.timezone'))->startOfMinute();
        $atendimento = $this->agendamentos->criar($cliente, $vinculo, $servico, $inicio);

        return (new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES)))
            ->response()->setStatusCode(201);
    }
}
