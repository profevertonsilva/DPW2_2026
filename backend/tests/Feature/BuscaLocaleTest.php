<?php

use App\Models\Atendimento;
use Illuminate\Support\Facades\DB;

/*
| Buscas sem diferenciar maiúsculas com o mesmo resultado em qualquer locale do
| banco (DECISOES 9.1). As colunas buscadas passam a usar a collation "C", como
| num banco criado com locale C. O DDL roda dentro da transação do
| RefreshDatabase e é desfeito ao fim de cada teste.
*/

function colunasEmLocaleC(string $tabela, array $colunas): void
{
    foreach ($colunas as $coluna) {
        $tipo = DB::scalar('select format_type(atttypid, atttypmod) from pg_attribute where attrelid = ?::regclass and attname = ?', [$tabela, $coluna]);
        DB::statement("alter table {$tabela} alter column {$coluna} type {$tipo} collate \"C\"");
    }
}

it('busca barbearias por nome e cidade sem diferenciar maiúsculas num banco com locale C', function () {
    colunasEmLocaleC('estabelecimento', ['nome', 'cidade']);
    barbearia(sobrescrever: ['nome' => 'Barbearia Água Viva', 'cidade' => 'Águas da Prata']);

    // Premissa: com a collation C, lower() não converte letras acentuadas.
    expect(DB::scalar('select lower(cidade) from estabelecimento limit 1'))->toBe('Águas da prata');

    foreach (['cidade=águas', 'cidade=ÁGUAS', 'cidade=Águas da PRATA', 'busca=água viva', 'busca=ÁGUA', 'busca=barbearia água'] as $filtro) {
        $this->getJson("/api/barbearias?{$filtro}")
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Barbearia Água Viva');
    }

    // Acentos continuam diferenciados, como antes.
    $this->getJson('/api/barbearias?cidade=aguas')->assertOk()->assertJsonCount(0);
    $this->getJson('/api/barbearias?busca=agua')->assertOk()->assertJsonCount(0);
});

it('busca clientes no agendamento manual sem diferenciar maiúsculas num banco com locale C', function () {
    $dono = conta('Dono');
    $barbearia = barbearia($dono);
    $corte = servico($barbearia);
    $barbeiro = barbeiro($barbearia, [$corte]);
    atendimento($barbeiro, $corte, '2026-11-10 10:00', conta('Ângela Íris', 'angela@teste.test'), Atendimento::REALIZADO);
    colunasEmLocaleC('cliente', ['nome', 'telefone', 'email']);
    entrarComo($dono);

    foreach (['ângela', 'ÂNGELA', 'íris', 'ÍRIS'] as $busca) {
        $this->getJson("/api/painel/estabelecimentos/{$barbearia->id}/clientes?busca={$busca}")
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Ângela Íris');
    }

    $this->getJson("/api/painel/estabelecimentos/{$barbearia->id}/clientes?busca=angela iris")
        ->assertOk()->assertJsonCount(0);
});
