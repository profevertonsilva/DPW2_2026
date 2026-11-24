<?php

namespace App\Http\Controllers\Painel;

use App\Dominio\Barbearias;
use App\Http\Controllers\Controller;
use App\Http\Requests\JornadaRequest;
use App\Http\Resources\MembroEquipeResource;
use App\Models\Estabelecimento;
use App\Models\VinculoEstabelecimento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Equipe, papéis, serviços e jornada (RF #11, #12, #13). Proprietário e gerente; a lista também para o atendente. */
class EquipeController extends Controller
{
    private const RELACOES = ['profissional', 'servicos', 'jornadas'];

    /** Papéis que se atribuem pela equipe (RF #11); proprietário é só de quem cadastrou (RF #12). */
    private const PAPEIS_ATRIBUIVEIS = [VinculoEstabelecimento::GERENTE, VinculoEstabelecimento::ATENDENTE, VinculoEstabelecimento::PROFISSIONAL];

    public function __construct(private readonly Barbearias $barbearias) {}

    public function index(Estabelecimento $estabelecimento): AnonymousResourceCollection
    {
        $vinculos = $estabelecimento->vinculos()->ativos()->with(self::RELACOES)->orderBy('id')->get();

        return MembroEquipeResource::collection($vinculos);
    }

    /** Adiciona à equipe alguém que já tem conta, pelo e-mail, com o papel inicial (RF #11). */
    public function store(Request $request, Estabelecimento $estabelecimento): JsonResponse
    {
        $dados = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'papel' => ['nullable', 'in:'.implode(',', self::PAPEIS_ATRIBUIVEIS)],
        ]);
        $papel = $dados['papel'] ?? VinculoEstabelecimento::PROFISSIONAL;
        $this->exigirPodeAtribuir($request, $papel);

        $vinculo = $this->barbearias->adicionarBarbeiro($estabelecimento, $dados['email'], $papel);

        return (new MembroEquipeResource($vinculo->load(self::RELACOES)))->response()->setStatusCode(201);
    }

    /** Alteração de cargo (RF #12). O papel de proprietário não se atribui nem se altera. */
    public function papel(Request $request, Estabelecimento $estabelecimento, VinculoEstabelecimento $vinculo): MembroEquipeResource
    {
        $this->exigirAtivo($vinculo);
        $novo = $request->validate(['papel' => ['required', 'in:'.implode(',', self::PAPEIS_ATRIBUIVEIS)]])['papel'];

        // Mexer em quem é (ou vai ser) gerente é exclusivo do proprietário.
        $this->exigirPodeAtribuir($request, $novo);
        $this->exigirPodeAtribuir($request, $vinculo->papel);

        $this->barbearias->alterarPapel($vinculo, $novo);

        return new MembroEquipeResource($vinculo->load(self::RELACOES));
    }

    /** Encerra o vínculo (status 'i' e desvinculado_em), sem apagar registros. */
    public function destroy(Request $request, Estabelecimento $estabelecimento, VinculoEstabelecimento $vinculo): MembroEquipeResource
    {
        // O gerente não remove outro gerente (DECISOES 4.18).
        $this->exigirPodeAtribuir($request, $vinculo->papel);
        $this->barbearias->removerBarbeiro($vinculo);

        return new MembroEquipeResource($vinculo->load(self::RELACOES));
    }

    public function servicos(Request $request, Estabelecimento $estabelecimento, VinculoEstabelecimento $vinculo): MembroEquipeResource
    {
        $this->exigirAtivo($vinculo);

        $dados = $request->validate([
            // RF #13: pelo menos um serviço.
            'servico_ids' => ['required', 'array', 'min:1'],
            'servico_ids.*' => ['integer', 'distinct'],
        ]);

        $this->barbearias->definirServicos($vinculo, array_map('intval', $dados['servico_ids']));

        return new MembroEquipeResource($vinculo->load(self::RELACOES));
    }

    public function jornada(JornadaRequest $request, Estabelecimento $estabelecimento, VinculoEstabelecimento $vinculo): MembroEquipeResource
    {
        $this->exigirAtivo($vinculo);

        $this->barbearias->definirJornada($vinculo, $request->validated('faixas'));

        return new MembroEquipeResource($vinculo->load(self::RELACOES));
    }

    private function exigirAtivo(VinculoEstabelecimento $vinculo): void
    {
        abort_unless($vinculo->ativo(), 404, 'Barbeiro não encontrado na equipe.');
    }

    /** RF #12: só o proprietário promove a gerente (ou mexe em quem já é gerente). */
    private function exigirPodeAtribuir(Request $request, string $papel): void
    {
        abort_if(
            $papel === VinculoEstabelecimento::GERENTE && ! $request->attributes->get('vinculo')->proprietario(),
            403,
            'Somente o proprietário pode atribuir, alterar ou remover o papel de gerente.',
        );
    }
}
