<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\Estabelecimento;
use App\Support\Datas;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Consulta da trilha de auditoria (RF #38, tela 46): só leitura, com filtro por período e usuário. */
class AuditoriaController extends Controller
{
    private const NOMES = [
        Auditoria::CANCELAMENTO => 'Cancelamento',
        Auditoria::REMARCACAO => 'Remarcação',
        Auditoria::ALTERACAO_CARGO => 'Alteração de cargo',
        Auditoria::REMOCAO_COLABORADOR => 'Remoção de colaborador',
        Auditoria::ALTERACAO_ESTABELECIMENTO => 'Alteração da barbearia',
    ];

    private const ENTIDADES = [
        'atendimento' => 'Agendamento',
        'vinculo_estabelecimento' => 'Colaborador',
        'estabelecimento' => 'Barbearia',
    ];

    public function index(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $dados = $request->validate([
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d'],
            'usuario_id' => ['nullable', 'integer'],
        ]);
        $fuso = config('app.timezone');
        $de = isset($dados['de']) ? CarbonImmutable::createFromFormat('Y-m-d', $dados['de'], $fuso)->startOfDay() : CarbonImmutable::now()->subDays(30)->startOfDay();
        $ate = isset($dados['ate']) ? CarbonImmutable::createFromFormat('Y-m-d', $dados['ate'], $fuso)->startOfDay()->addDay() : null;

        $base = Auditoria::query()->where('estabelecimento_id', $estabelecimento->id);

        $registros = (clone $base)
            ->with('responsavel.cliente')
            ->where('data_hora', '>=', $de)
            ->when($ate, fn ($q) => $q->where('data_hora', '<', $ate))
            ->when($dados['usuario_id'] ?? null, fn ($q, $id) => $q->where('usuario_responsavel', $id))
            ->orderByDesc('data_hora')->orderByDesc('id')
            ->limit(300)
            ->get();

        // Para o filtro por usuário: todos que já aparecem na trilha desta barbearia.
        $usuarios = (clone $base)->with('responsavel.cliente')->get()
            ->unique('usuario_responsavel')
            ->map(fn (Auditoria $a) => ['id' => $a->usuario_responsavel, 'nome' => $a->responsavel?->cliente?->nome ?? $a->responsavel?->email])
            ->sortBy('nome')->values();

        return response()->json([
            'usuarios' => $usuarios,
            'registros' => $registros->map(fn (Auditoria $a) => [
                'id' => $a->id,
                'data_hora' => Datas::iso($a->data_hora),
                'data' => Datas::data($a->data_hora),
                'hora' => Datas::hora($a->data_hora),
                'usuario' => ['id' => $a->usuario_responsavel, 'nome' => $a->responsavel?->cliente?->nome ?? $a->responsavel?->email],
                'tipo_operacao' => $a->tipo_operacao,
                'operacao' => self::NOMES[$a->tipo_operacao] ?? $a->tipo_operacao,
                'entidade' => $a->entidade,
                'entidade_nome' => self::ENTIDADES[$a->entidade] ?? $a->entidade,
                'entidade_afetada' => $a->entidade_afetada,
                'valor_anterior' => $a->valor_anterior,
                'valor_novo' => $a->valor_novo,
            ]),
        ]);
    }
}
