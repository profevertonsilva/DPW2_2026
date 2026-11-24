<?php

namespace App\Http\Resources;

use App\Models\Atendimento;
use App\Support\Datas;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Agendamento (atendimento), usado pelo app do cliente e pelo painel. */
class AgendamentoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $servico = $this->servicos->first();
        $estabelecimento = $this->estabelecimento;
        $futuroAgendado = $this->status === Atendimento::AGENDADO && $this->inicio->greaterThan(CarbonImmutable::now());

        return [
            'id' => $this->id,
            'status' => $this->status,
            'inicio' => Datas::iso($this->inicio),
            'fim' => Datas::iso($this->fim),
            'data' => Datas::data($this->inicio),
            'hora_inicio' => Datas::hora($this->inicio),
            'hora_fim' => Datas::hora($this->fim),
            'valor' => (float) $this->valor_final,
            'servico' => $servico ? [
                'id' => $servico->id,
                'nome' => $servico->nome,
                'duracao_minutos' => $servico->duracao_minutos,
            ] : null,
            'barbeiro' => [
                'vinculo_id' => $this->vinculo_estabelecimento_id,
                'nome' => $this->vinculo->profissional->nome,
            ],
            'barbearia' => [
                'id' => $estabelecimento->id,
                'nome' => $estabelecimento->nome,
                'telefone' => $estabelecimento->telefones->first()?->telefone,
                'endereco' => trim("{$estabelecimento->logradouro}, {$estabelecimento->numero} - {$estabelecimento->bairro}, {$estabelecimento->cidade}/{$estabelecimento->estado}"),
            ],
            'cliente' => [
                'nome' => $this->cliente->nome,
                'telefone' => $this->cliente->telefone,
            ],
            'cancelada_em' => Datas::iso($this->cancelada_em),
            'motivo_cancelamento' => $this->motivo_cancelamento,
            'presenca_confirmada_em' => Datas::iso($this->presenca_confirmada_em),
            'pode_cancelar' => $futuroAgendado,
            'pode_remarcar' => $futuroAgendado,
            // RF #30: só atendimento realizado; editável em até 7 dias após o envio.
            'avaliacao' => $this->avaliacao ? [
                'nota' => $this->avaliacao->nota,
                'comentario' => $this->avaliacao->comentario,
                'editavel_ate' => Datas::iso($this->avaliacao->editavelAte()),
            ] : null,
            'pode_avaliar' => $this->status === Atendimento::REALIZADO && (! $this->avaliacao || $this->avaliacao->editavel()),
        ];
    }

    /** Relações necessárias para montar o recurso sem consultas extras. */
    public const RELACOES = ['servicos', 'estabelecimento.telefones', 'vinculo.profissional', 'cliente', 'avaliacao'];
}
