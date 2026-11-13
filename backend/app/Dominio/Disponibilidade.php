<?php

namespace App\Dominio;

use App\Models\Atendimento;
use App\Models\BloqueioHorario;
use App\Models\HorarioFuncionamento;
use App\Models\Jornada;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;

/**
 * Cálculo de horários disponíveis (RF #19), conforme as seções 5.5 e 6.3 do briefing.
 *
 * Janela de trabalho de um barbeiro em uma data:
 * - sem nenhuma jornada cadastrada: o horário de funcionamento da barbearia no dia;
 * - com jornada: as faixas daquele dia da semana recortadas pelo funcionamento
 *   (interseção); dia sem faixa é folga;
 * - barbearia fechada no dia: nenhum horário.
 *
 * Um início vale se o serviço inteiro couber na faixa, se não sobrepuser
 * atendimento ativo nem bloqueio, e se estiver no futuro.
 */
class Disponibilidade
{
    /**
     * Faixas de trabalho do vínculo na data, como pares [inicio, fim].
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    public function janelas(VinculoEstabelecimento $vinculo, CarbonImmutable $data): array
    {
        // A data é sempre interpretada no fuso da aplicação (RNF #09).
        $dia = $data->setTimezone(config('app.timezone'))->startOfDay();
        $diaSemana = $dia->dayOfWeek;

        $funcionamento = HorarioFuncionamento::query()
            ->where('estabelecimento_id', $vinculo->estabelecimento_id)
            ->where('dia_semana', $diaSemana)
            ->where('aberto', true)
            ->get()
            ->map(fn (HorarioFuncionamento $h) => [
                $dia->setTimeFromTimeString($h->hora_abertura),
                $dia->setTimeFromTimeString($h->hora_fechamento),
            ])
            // Faixas não atravessam a meia-noite (seção 6.3).
            ->filter(fn (array $f) => $f[1]->greaterThan($f[0]))
            ->values()
            ->all();

        if ($funcionamento === []) {
            return [];
        }

        $temJornada = Jornada::query()->where('vinculo_estabelecimento_id', $vinculo->id)->exists();

        if (! $temJornada) {
            return $this->ordenar($funcionamento);
        }

        $faixas = Jornada::query()
            ->where('vinculo_estabelecimento_id', $vinculo->id)
            ->where('dia_semana', $diaSemana)
            ->get();

        $janelas = [];
        foreach ($faixas as $faixa) {
            $inicio = $dia->setTimeFromTimeString($faixa->hora_inicio);
            $fim = $dia->setTimeFromTimeString($faixa->hora_fim);

            foreach ($funcionamento as [$abre, $fecha]) {
                $i = $inicio->max($abre);
                $f = $fim->min($fecha);
                if ($f->greaterThan($i)) {
                    $janelas[] = [$i, $f];
                }
            }
        }

        return $this->ordenar($janelas);
    }

    /**
     * Inícios livres para o vínculo, um serviço com a duração dada e a data.
     *
     * @return list<CarbonImmutable>
     */
    public function horarios(
        VinculoEstabelecimento $vinculo,
        int $duracaoMinutos,
        CarbonImmutable $data,
        ?int $ignorarAtendimentoId = null,
    ): array {
        $janelas = $this->janelas($vinculo, $data);

        if ($janelas === []) {
            return [];
        }

        $passo = max(1, (int) config('agendaki.intervalo_minutos'));
        $agora = CarbonImmutable::now();
        $ultimoFim = max(array_map(fn (array $j) => $j[1], $janelas));
        $ocupados = $this->ocupados($vinculo, $janelas[0][0], $ultimoFim, $ignorarAtendimentoId);

        $livres = [];
        foreach ($janelas as [$inicioJanela, $fimJanela]) {
            for ($inicio = $inicioJanela; $inicio->addMinutes($duracaoMinutos) <= $fimJanela; $inicio = $inicio->addMinutes($passo)) {
                $fim = $inicio->addMinutes($duracaoMinutos);

                if ($inicio <= $agora || $this->sobrepoe($inicio, $fim, $ocupados)) {
                    continue;
                }

                $livres[] = $inicio;
            }
        }

        return $livres;
    }

    /** O intervalo [inicio, fim) cabe inteiro em uma faixa de trabalho do vínculo? */
    public function cabeNaJanela(VinculoEstabelecimento $vinculo, CarbonImmutable $inicio, CarbonImmutable $fim): bool
    {
        foreach ($this->janelas($vinculo, $inicio) as [$i, $f]) {
            if ($inicio >= $i && $fim <= $f) {
                return true;
            }
        }

        return false;
    }

    /** Há atendimento ativo ou bloqueio sobreposto ao intervalo [inicio, fim)? */
    public function ocupado(
        VinculoEstabelecimento $vinculo,
        CarbonImmutable $inicio,
        CarbonImmutable $fim,
        ?int $ignorarAtendimentoId = null,
    ): bool {
        return $this->ocupados($vinculo, $inicio, $fim, $ignorarAtendimentoId) !== [];
    }

    /**
     * Até N horários livres mais próximos do pedido, no mesmo dia e com o mesmo
     * barbeiro, em ordem cronológica (resposta 409, seção 7).
     *
     * @return list<CarbonImmutable>
     */
    public function alternativas(
        VinculoEstabelecimento $vinculo,
        int $duracaoMinutos,
        CarbonImmutable $pedido,
        ?int $ignorarAtendimentoId = null,
    ): array {
        $livres = $this->horarios($vinculo, $duracaoMinutos, $pedido, $ignorarAtendimentoId);

        usort($livres, fn ($a, $b) => abs($a->diffInSeconds($pedido)) <=> abs($b->diffInSeconds($pedido)) ?: $a <=> $b);

        $escolhidos = array_slice($livres, 0, (int) config('agendaki.alternativas'));
        usort($escolhidos, fn ($a, $b) => $a <=> $b);

        return $escolhidos;
    }

    /**
     * Intervalos ocupados (atendimentos ativos e bloqueios) que tocam [de, ate).
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function ocupados(
        VinculoEstabelecimento $vinculo,
        CarbonImmutable $de,
        CarbonImmutable $ate,
        ?int $ignorarAtendimentoId,
    ): array {
        $atendimentos = Atendimento::query()
            ->ativos()
            ->where('vinculo_estabelecimento_id', $vinculo->id)
            ->where('inicio', '<', $ate)
            ->where('fim', '>', $de)
            ->when($ignorarAtendimentoId, fn ($q) => $q->where('id', '<>', $ignorarAtendimentoId))
            ->get(['inicio', 'fim'])
            ->map(fn (Atendimento $a) => [$a->inicio, $a->fim]);

        $bloqueios = BloqueioHorario::query()
            ->where('vinculo_estabelecimento_id', $vinculo->id)
            ->where('inicio', '<', $ate)
            ->where('fim', '>', $de)
            ->get(['inicio', 'fim'])
            ->map(fn (BloqueioHorario $b) => [$b->inicio, $b->fim]);

        return $atendimentos->concat($bloqueios)->values()->all();
    }

    /** Intervalos semiabertos: [a, b) e [c, d) se sobrepõem se a < d e c < b. */
    private function sobrepoe(CarbonImmutable $inicio, CarbonImmutable $fim, array $ocupados): bool
    {
        foreach ($ocupados as [$i, $f]) {
            if ($inicio < $f && $i < $fim) {
                return true;
            }
        }

        return false;
    }

    private function ordenar(array $janelas): array
    {
        usort($janelas, fn ($a, $b) => $a[0] <=> $b[0]);

        return array_values($janelas);
    }
}
