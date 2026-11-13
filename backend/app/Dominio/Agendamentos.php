<?php

namespace App\Dominio;

use App\Exceptions\HorarioIndisponivel;
use App\Models\Atendimento;
use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use App\Support\Datas;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Criação, remarcação, cancelamento e conclusão de agendamentos (seção 6.3).
 *
 * Proteção contra sobreposição (seção 5.1, RF #36): a verificação prévia
 * evita a maioria dos conflitos, mas a garantia é a restrição de exclusão
 * `ex_atendimento_sem_sobreposicao`. A violação dela (SQLSTATE 23P01) é
 * capturada FORA do DB::transaction: capturada dentro, o PostgreSQL deixaria
 * a transação abortada; fora, o rollback devolve uma conexão utilizável.
 */
class Agendamentos
{
    private const VIOLACAO_EXCLUSAO = '23P01';

    public function __construct(private readonly Disponibilidade $disponibilidade) {}

    public function criar(Cliente $cliente, VinculoEstabelecimento $vinculo, Servico $servico, CarbonImmutable $inicio): Atendimento
    {
        $this->validarServicoDoBarbeiro($vinculo, $servico);

        $fim = $inicio->addMinutes($servico->duracao_minutos);
        $this->validarHorario($vinculo, $inicio, $fim);

        try {
            return DB::transaction(function () use ($cliente, $vinculo, $servico, $inicio, $fim) {
                if ($this->disponibilidade->ocupado($vinculo, $inicio, $fim)) {
                    throw new HorarioIndisponivel;
                }

                // Exigida pela chave estrangeira composta de atendimento.
                DB::table('cliente_estabelecimento')->insertOrIgnore([
                    'cliente_id' => $cliente->id,
                    'estabelecimento_id' => $vinculo->estabelecimento_id,
                ]);

                $atendimento = Atendimento::create([
                    'cliente_id' => $cliente->id,
                    'estabelecimento_id' => $vinculo->estabelecimento_id,
                    'vinculo_estabelecimento_id' => $vinculo->id,
                    'inicio' => $inicio,
                    'fim' => $fim,
                    'valor_final' => $servico->preco,
                    // Explícito: o default da tabela é 'reservado' (RF #35).
                    'status' => Atendimento::AGENDADO,
                ]);

                $atendimento->servicos()->attach($servico->id, ['valor' => $servico->preco]);

                return $atendimento;
            });
        } catch (HorarioIndisponivel|QueryException $e) {
            throw $this->conflito($e, $vinculo, $servico->duracao_minutos, $inicio);
        }
    }

    /** Altera início e fim do mesmo atendimento, mantendo serviço, barbeiro e duração. */
    public function remarcar(Atendimento $atendimento, CarbonImmutable $inicio): Atendimento
    {
        $this->exigirFuturoAgendado($atendimento, 'remarcar');

        $vinculo = $atendimento->vinculo;
        $duracao = (int) $atendimento->inicio->diffInMinutes($atendimento->fim);
        $fim = $inicio->addMinutes($duracao);

        if (! $vinculo->ativo()) {
            throw ValidationException::withMessages([
                'inicio' => 'O barbeiro deste agendamento não atende mais nesta barbearia.',
            ]);
        }

        $this->validarHorario($vinculo, $inicio, $fim);
        $anterior = ['inicio' => Datas::data($atendimento->inicio).' '.Datas::hora($atendimento->inicio)];

        try {
            DB::transaction(function () use ($atendimento, $vinculo, $inicio, $fim, $anterior) {
                if ($this->disponibilidade->ocupado($vinculo, $inicio, $fim, $atendimento->id)) {
                    throw new HorarioIndisponivel;
                }

                $atendimento->update(['inicio' => $inicio, 'fim' => $fim]);

                Trilha::registrar($atendimento->estabelecimento_id, Auditoria::REMARCACAO, 'atendimento', $atendimento->id,
                    $anterior, ['inicio' => Datas::data($inicio).' '.Datas::hora($inicio)]);
            });
        } catch (HorarioIndisponivel|QueryException $e) {
            throw $this->conflito($e, $vinculo, $duracao, $inicio, $atendimento->id);
        }

        return $atendimento->refresh();
    }

    /** Cancelamento pelo cliente: só agendamentos futuros com status 'agendado'. */
    public function cancelarPeloCliente(Atendimento $atendimento, ?string $motivo): Atendimento
    {
        $this->exigirFuturoAgendado($atendimento, 'cancelar');

        return $this->cancelar($atendimento, $motivo);
    }

    /** Cancelamento pelo painel (proprietário ou o barbeiro do atendimento). */
    public function cancelar(Atendimento $atendimento, ?string $motivo): Atendimento
    {
        if ($atendimento->status !== Atendimento::AGENDADO) {
            throw ValidationException::withMessages([
                'status' => 'Só é possível cancelar agendamentos com status "agendado".',
            ]);
        }

        DB::transaction(function () use ($atendimento, $motivo) {
            $atendimento->update([
                'status' => Atendimento::CANCELADO,
                'cancelada_em' => CarbonImmutable::now(),
                'motivo_cancelamento' => $motivo,
            ]);

            Trilha::registrar($atendimento->estabelecimento_id, Auditoria::CANCELAMENTO, 'atendimento', $atendimento->id,
                ['status' => Atendimento::AGENDADO], array_filter(['status' => Atendimento::CANCELADO, 'motivo' => $motivo]));
        });

        return $atendimento;
    }

    /** Check-in manual (RF #23, sem QR Code): a partir do dia do atendimento. */
    public function marcarRealizado(Atendimento $atendimento): Atendimento
    {
        if ($atendimento->status !== Atendimento::AGENDADO) {
            throw ValidationException::withMessages([
                'status' => 'Só é possível marcar como realizado um agendamento com status "agendado".',
            ]);
        }

        $hoje = CarbonImmutable::now()->startOfDay();
        if ($atendimento->inicio->setTimezone(config('app.timezone'))->startOfDay()->greaterThan($hoje)) {
            throw ValidationException::withMessages([
                'status' => 'O atendimento só pode ser marcado como realizado a partir do dia agendado.',
            ]);
        }

        $atendimento->update([
            'status' => Atendimento::REALIZADO,
            // Pendência 5.10: instante em que o atendimento é marcado como realizado.
            'presenca_confirmada_em' => CarbonImmutable::now(),
        ]);

        return $atendimento;
    }

    private function validarServicoDoBarbeiro(VinculoEstabelecimento $vinculo, Servico $servico): void
    {
        if (! $vinculo->ativo() || $vinculo->estabelecimento?->status !== 'a') {
            throw ValidationException::withMessages(['vinculo_id' => 'Barbeiro não encontrado nesta barbearia.']);
        }

        if ((int) $servico->estabelecimento_id !== (int) $vinculo->estabelecimento_id || ! $servico->ativo()) {
            throw ValidationException::withMessages(['servico_id' => 'Serviço não encontrado nesta barbearia.']);
        }

        if (! $vinculo->servicos()->whereKey($servico->id)->exists()) {
            throw ValidationException::withMessages(['servico_id' => 'Este barbeiro não executa o serviço escolhido.']);
        }
    }

    private function validarHorario(VinculoEstabelecimento $vinculo, CarbonImmutable $inicio, CarbonImmutable $fim): void
    {
        if ($inicio->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw ValidationException::withMessages(['inicio' => 'Escolha um horário no futuro.']);
        }

        if (! $this->disponibilidade->cabeNaJanela($vinculo, $inicio, $fim)) {
            throw ValidationException::withMessages(['inicio' => 'Horário fora do expediente do barbeiro.']);
        }
    }

    private function exigirFuturoAgendado(Atendimento $atendimento, string $acao): void
    {
        if ($atendimento->status !== Atendimento::AGENDADO || $atendimento->inicio->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw ValidationException::withMessages([
                'status' => "Só é possível {$acao} agendamentos futuros com status \"agendado\".",
            ]);
        }
    }

    /** Converte conflito (prévio ou do banco) em 409 com alternativas; repassa outros erros. */
    private function conflito(
        HorarioIndisponivel|QueryException $e,
        VinculoEstabelecimento $vinculo,
        int $duracao,
        CarbonImmutable $inicio,
        ?int $ignorarAtendimentoId = null,
    ): \Throwable {
        if ($e instanceof QueryException && $e->getCode() !== self::VIOLACAO_EXCLUSAO) {
            return $e;
        }

        return new HorarioIndisponivel(
            $this->disponibilidade->alternativas($vinculo, $duracao, $inicio, $ignorarAtendimentoId),
        );
    }
}
