<?php

namespace App\Http\Controllers\Painel;

use App\Dominio\Barbearias;
use App\Http\Controllers\Controller;
use App\Http\Requests\BarbeariaRequest;
use App\Http\Resources\BarbeariaResource;
use App\Models\Estabelecimento;
use App\Models\VinculoEstabelecimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Barbearias do usuário no painel (RF #09). */
class EstabelecimentoController extends Controller
{
    public function __construct(private readonly Barbearias $barbearias) {}

    /** Barbearias em que o usuário tem vínculo ativo, com o papel. */
    public function index(Request $request): JsonResponse
    {
        $profissional = $request->user()->profissional;

        $vinculos = $profissional
            ? $profissional->vinculos()->ativos()
                ->whereHas('estabelecimento', fn ($q) => $q->where('status', 'a'))
                ->with('estabelecimento')->orderBy('id')->get()
            : collect();

        return response()->json($vinculos->map(fn (VinculoEstabelecimento $v) => [
            'id' => $v->estabelecimento_id,
            'nome' => $v->estabelecimento->nome,
            'cidade' => $v->estabelecimento->cidade,
            'estado' => $v->estabelecimento->estado,
            'papel' => $v->papel,
            'vinculo_id' => $v->id,
        ])->values());
    }

    /** Cadastra a barbearia; o usuário se torna proprietário (RF #03, #09). */
    public function store(BarbeariaRequest $request): JsonResponse
    {
        $estabelecimento = $this->barbearias->cadastrar($request->user(), $request->validated());

        return response()->json($this->detalhe($estabelecimento, VinculoEstabelecimento::PROPRIETARIO), 201);
    }

    public function show(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        return response()->json($this->detalhe($estabelecimento, $request->attributes->get('vinculo')->papel));
    }

    public function update(BarbeariaRequest $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $estabelecimento = $this->barbearias->atualizar($estabelecimento, $request->validated());

        return response()->json($this->detalhe($estabelecimento, $request->attributes->get('vinculo')->papel));
    }

    private function detalhe(Estabelecimento $estabelecimento, string $papel): array
    {
        $estabelecimento->load(['telefones', 'horarios', 'servicos', 'vinculos.profissional', 'vinculos.servicos'])->carregarAvaliacao();

        return [
            ...(new BarbeariaResource($estabelecimento))->completo()->resolve(),
            'status' => $estabelecimento->status,
            'papel' => $papel,
        ];
    }
}
