<?php

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServicoResource;
use App\Models\Estabelecimento;
use App\Models\Servico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Catálogo de serviços da barbearia (RF #10). Proprietário e gerente. */
class ServicoController extends Controller
{
    public function index(Estabelecimento $estabelecimento): AnonymousResourceCollection
    {
        // Ativos primeiro; os inativos continuam listados para reativação.
        return ServicoResource::collection(
            $estabelecimento->servicos()->orderBy('status')->orderBy('nome')->get(),
        );
    }

    public function store(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $servico = $estabelecimento->servicos()->create([...$this->validar($request), 'status' => 'a']);

        return (new ServicoResource($servico->refresh()))->response()->setStatusCode(201);
    }

    public function update(Request $request, Estabelecimento $estabelecimento, Servico $servico): ServicoResource
    {
        $dados = $this->validar($request, comStatus: true);
        $servico->update($dados);

        return new ServicoResource($servico);
    }

    /**
     * "Excluir" inativa o serviço (pendência 5.10): atendimentos antigos o
     * referenciam. Ele deixa de aparecer no app e de aceitar agendamentos.
     */
    public function destroy(Estabelecimento $estabelecimento, Servico $servico): ServicoResource
    {
        $servico->update(['status' => 'i']);

        return new ServicoResource($servico);
    }

    private function validar(Request $request, bool $comStatus = false): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'preco' => ['required', 'numeric', 'gt:0', 'max:99999999.99', 'decimal:0,2'],
            'duracao_minutos' => ['required', 'integer', 'min:1', 'max:1440'],
            ...($comStatus ? ['status' => ['sometimes', 'in:a,i']] : []),
        ]);
    }
}
