<?php

use App\Dominio\Barbearias;
use Carbon\CarbonImmutable;

/*
| RF #19: cálculo de horários disponíveis (seção 9.1).
| Hoje é segunda, 16/11/2026, 08:00. Terça = 17/11; domingo = 22/11.
*/

beforeEach(function () {
    $this->barbearia = barbearia(sobrescrever: ['horarios' => semana([0 => null])]); // fechada aos domingos
    $this->corte = servico($this->barbearia, 30);
    $this->barbeiro = barbeiro($this->barbearia, [$this->corte]);
});

it('gera inícios a cada 30 minutos dentro do horário de funcionamento', function () {
    $horas = grade($this->barbeiro, $this->corte, '2026-11-17');

    expect($horas)->toHaveCount(18)
        ->and($horas[0])->toBe('09:00')
        ->and(end($horas))->toBe('17:30');
});

it('não mostra serviço que não cabe antes do fim da faixa', function () {
    $longo = servico($this->barbearia, 90, 80, 'Longo');
    app(Barbearias::class)->definirServicos($this->barbeiro, [$this->corte->id, $longo->id]);

    $horas = grade($this->barbeiro, $longo, '2026-11-17');

    expect(end($horas))->toBe('16:30') // 16:30 + 90 min = 18:00
        ->and($horas)->not->toContain('17:00');
});

it('remove os horários bloqueados', function () {
    bloqueio($this->barbeiro, '2026-11-17 12:00', '2026-11-17 14:00');

    $horas = grade($this->barbeiro, $this->corte, '2026-11-17');

    expect($horas)->toContain('11:30', '14:00')
        ->not->toContain('12:00', '12:30', '13:00', '13:30');
});

it('remove os horários de atendimentos ativos', function () {
    atendimento($this->barbeiro, $this->corte, '2026-11-17 10:00');

    expect(grade($this->barbeiro, $this->corte, '2026-11-17'))
        ->toContain('09:30', '10:30')->not->toContain('10:00');
});

it('resulta em grade vazia no dia de folga do barbeiro', function () {
    // Jornada só de terça a sábado: segunda é folga.
    app(Barbearias::class)->definirJornada($this->barbeiro, array_map(
        fn ($d) => ['dia_semana' => $d, 'hora_inicio' => '09:00', 'hora_fim' => '18:00'],
        [2, 3, 4, 5, 6],
    ));

    expect(grade($this->barbeiro, $this->corte, '2026-11-23'))->toBe([]) // segunda
        ->and(grade($this->barbeiro, $this->corte, '2026-11-24'))->not->toBeEmpty(); // terça
});

it('resulta em grade vazia com a barbearia fechada', function () {
    expect(grade($this->barbeiro, $this->corte, '2026-11-22'))->toBe([]); // domingo
});

it('não mostra horários que já passaram', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:10', 'America/Sao_Paulo'));

    expect(grade($this->barbeiro, $this->corte, '2026-11-17')[0])->toBe('10:30')
        ->and(grade($this->barbeiro, $this->corte, '2026-11-16'))->toBe([]);
});

it('recorta a jornada pelo horário de funcionamento', function () {
    app(Barbearias::class)->definirJornada($this->barbeiro, [
        ['dia_semana' => 2, 'hora_inicio' => '07:00', 'hora_fim' => '20:00'],
    ]);

    $horas = grade($this->barbeiro, $this->corte, '2026-11-17');

    expect($horas[0])->toBe('09:00')->and(end($horas))->toBe('17:30');
});

it('respeita a pausa entre duas faixas no mesmo dia', function () {
    app(Barbearias::class)->definirJornada($this->barbeiro, [
        ['dia_semana' => 2, 'hora_inicio' => '09:00', 'hora_fim' => '12:00'],
        ['dia_semana' => 2, 'hora_inicio' => '13:00', 'hora_fim' => '18:00'],
    ]);

    expect(grade($this->barbeiro, $this->corte, '2026-11-17'))
        ->toContain('11:30', '13:00')->not->toContain('12:00', '12:30');
});

it('valida o serviço e o barbeiro da consulta', function () {
    $outra = barbearia();
    $servicoDeOutra = servico($outra);

    $this->getJson("/api/barbearias/{$this->barbearia->id}/disponibilidade?servico_id={$servicoDeOutra->id}&vinculo_id={$this->barbeiro->id}&data=2026-11-17")
        ->assertUnprocessable()->assertJsonValidationErrors(['servico_id']);

    $semServico = barbeiro($this->barbearia);
    $this->getJson("/api/barbearias/{$this->barbearia->id}/disponibilidade?servico_id={$this->corte->id}&vinculo_id={$semServico->id}&data=2026-11-17")
        ->assertUnprocessable()->assertJsonPath('errors.servico_id.0', 'Este barbeiro não executa o serviço escolhido.');
});
