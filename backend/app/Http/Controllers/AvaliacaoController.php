<?php

namespace App\Http\Controllers;

use App\Http\Resources\AgendamentoResource;
use App\Models\Atendimento;
use App\Models\Avaliacao;
use App\Models\Estabelecimento;
use App\Support\Datas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Avaliação após atendimento realizado (RF #30, telas 26 e 06). */
class AvaliacaoController extends Controller
{
    /**
     * Cria ou edita a avaliação do próprio atendimento. Só atendimento
     * realizado; uma por atendimento; edição em até 7 dias após o envio.
     */
    public function salvar(Request $request, Atendimento $atendimento): AgendamentoResource
    {
        abort_if(
            (int) $atendimento->cliente_id !== (int) $request->user()->cliente?->id,
            403,
            'Este agendamento pertence a outro cliente.',
        );

        $dados = $request->validate([
            'nota' => ['required', 'integer', 'between:1,5'],
            'comentario' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($atendimento->status !== Atendimento::REALIZADO) {
            throw ValidationException::withMessages(['nota' => 'Só é possível avaliar um atendimento realizado.']);
        }

        $avaliacao = $atendimento->avaliacao;
        if ($avaliacao && ! $avaliacao->editavel()) {
            throw ValidationException::withMessages(['nota' => 'O prazo de 7 dias para editar esta avaliação terminou.']);
        }

        if ($avaliacao) {
            $avaliacao->update($dados);
        } else {
            $atendimento->avaliacao()->create([...$dados, 'status' => 'a']);
        }

        return new AgendamentoResource($atendimento->load(AgendamentoResource::RELACOES));
    }

    /** Avaliações visíveis da barbearia, com média e total. */
    public function index(Estabelecimento $estabelecimento): JsonResponse
    {
        abort_if($estabelecimento->status !== 'a', 404, 'Barbearia não encontrada.');

        $avaliacoes = Avaliacao::query()
            ->where('status', 'a')
            ->whereHas('atendimento', fn ($q) => $q->where('estabelecimento_id', $estabelecimento->id))
            ->with(['atendimento.cliente', 'atendimento.servicos'])
            ->orderByDesc('criado_em')
            ->limit(50)
            ->get();

        $resumo = $estabelecimento->avaliacoes()->where('avaliacao.status', 'a');

        return response()->json([
            'media' => ($media = (clone $resumo)->avg('avaliacao.nota')) !== null ? round((float) $media, 1) : null,
            'total' => (clone $resumo)->count(),
            'avaliacoes' => $avaliacoes->map(fn (Avaliacao $a) => [
                'id' => $a->id,
                // Privacidade: primeiro nome e inicial do sobrenome ("Mariana S.").
                'cliente' => $this->nomeCurto($a->atendimento->cliente->nome),
                'nota' => $a->nota,
                'comentario' => $a->comentario,
                'servico' => $a->atendimento->servicos->first()?->nome,
                'data' => Datas::data($a->criado_em),
            ]),
        ]);
    }

    private function nomeCurto(string $nome): string
    {
        $partes = preg_split('/\s+/', trim($nome));

        return count($partes) > 1 ? $partes[0].' '.Str::upper(Str::substr(end($partes), 0, 1)).'.' : $partes[0];
    }
}
