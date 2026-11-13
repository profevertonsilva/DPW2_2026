<?php

use App\Dominio\Barbearias;
use App\Models\Atendimento;
use App\Models\BloqueioHorario;
use App\Models\Estabelecimento;
use App\Models\Login;
use App\Models\Servico;
use App\Models\VinculoEstabelecimento;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/*
| Testes de Feature rodam contra o PostgreSQL de testes (agendaki_test,
| configurado no phpunit.xml), nunca em SQLite: a restrição de exclusão é
| recurso do PostgreSQL (seção 9.1 do briefing).
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Segunda-feira, 16/11/2026, 08:00 em São Paulo.
        $this->travelTo(CarbonImmutable::parse('2026-11-16 08:00:00', 'America/Sao_Paulo'));
    })
    ->in('Feature');

/*
| Apoio
*/

function conta(string $nome = 'Cliente Teste', ?string $email = null): Login
{
    $email ??= Str::lower(Str::random(10)).'@teste.test';

    $login = Login::create([
        'email' => $email,
        'senha_hash' => 'Senha@123',
        'tipo_usuario' => 'cliente',
        'status' => 'a',
    ]);
    $login->cliente()->create(['nome' => $nome, 'telefone' => '(19) 99999-0000', 'email' => $email]);

    return $login;
}

function entrarComo(Login $login): Login
{
    Sanctum::actingAs($login);

    return $login;
}

/** Sete dias abertos das 09:00 às 18:00, salvo os dias informados em $horarios. */
function semana(array $horarios = []): array
{
    return array_map(function (int $dia) use ($horarios) {
        $faixa = array_key_exists($dia, $horarios) ? $horarios[$dia] : ['09:00', '18:00'];

        return [
            'dia_semana' => $dia,
            'aberto' => $faixa !== null,
            'hora_abertura' => $faixa[0] ?? null,
            'hora_fechamento' => $faixa[1] ?? null,
        ];
    }, range(0, 6));
}

function dadosBarbearia(array $sobrescrever = []): array
{
    return [
        'nome' => 'Barbearia Teste',
        'telefone' => '(19) 3600-0000',
        'cep' => '13870-000',
        'estado' => 'SP',
        'cidade' => 'São João da Boa Vista',
        'bairro' => 'Centro',
        'logradouro' => 'Rua A',
        'numero' => 10,
        'horarios' => semana(),
        ...$sobrescrever,
    ];
}

function barbearia(?Login $dono = null, array $sobrescrever = []): Estabelecimento
{
    return app(Barbearias::class)->cadastrar($dono ?? conta('Dono'), dadosBarbearia($sobrescrever));
}

function vinculoDoDono(Estabelecimento $estabelecimento): VinculoEstabelecimento
{
    return VinculoEstabelecimento::where('estabelecimento_id', $estabelecimento->id)
        ->where('papel', VinculoEstabelecimento::PROPRIETARIO)->firstOrFail();
}

function servico(Estabelecimento $estabelecimento, int $duracao = 30, float $preco = 40.0, string $nome = 'Corte'): Servico
{
    return $estabelecimento->servicos()->create([
        'nome' => $nome, 'preco' => $preco, 'duracao_minutos' => $duracao, 'status' => 'a',
    ]);
}

/** Barbeiro com conta própria, vinculado e habilitado nos serviços informados. */
function barbeiro(Estabelecimento $estabelecimento, array $servicos = [], ?Login $login = null): VinculoEstabelecimento
{
    $login ??= conta('Barbeiro');
    $vinculo = app(Barbearias::class)->adicionarBarbeiro($estabelecimento, $login->email);

    if ($servicos !== []) {
        app(Barbearias::class)->definirServicos($vinculo, array_map(fn (Servico $s) => $s->id, $servicos));
    }

    return $vinculo;
}

function loginDo(VinculoEstabelecimento $vinculo): Login
{
    return $vinculo->profissional->login;
}

/** Instante em São Paulo, no formato aceito pela API. */
function sp(string $dataHora): string
{
    return CarbonImmutable::parse($dataHora, 'America/Sao_Paulo')->toIso8601String();
}

/** Cria um atendimento direto no banco (sem passar pela API). */
function atendimento(VinculoEstabelecimento $vinculo, Servico $servico, string $inicio, ?Login $cliente = null, string $status = Atendimento::AGENDADO): Atendimento
{
    $cliente ??= conta();
    $inicio = CarbonImmutable::parse($inicio, 'America/Sao_Paulo');

    DB::table('cliente_estabelecimento')->insertOrIgnore([
        'cliente_id' => $cliente->cliente->id,
        'estabelecimento_id' => $vinculo->estabelecimento_id,
    ]);

    $atendimento = Atendimento::create([
        'cliente_id' => $cliente->cliente->id,
        'estabelecimento_id' => $vinculo->estabelecimento_id,
        'vinculo_estabelecimento_id' => $vinculo->id,
        'inicio' => $inicio,
        'fim' => $inicio->addMinutes($servico->duracao_minutos),
        'valor_final' => $servico->preco,
        'status' => $status,
    ]);
    $atendimento->servicos()->attach($servico->id, ['valor' => $servico->preco]);

    return $atendimento;
}

function bloqueio(VinculoEstabelecimento $vinculo, string $inicio, string $fim): BloqueioHorario
{
    return BloqueioHorario::create([
        'vinculo_estabelecimento_id' => $vinculo->id,
        'inicio' => CarbonImmutable::parse($inicio, 'America/Sao_Paulo'),
        'fim' => CarbonImmutable::parse($fim, 'America/Sao_Paulo'),
    ]);
}

/** Horas ('HH:mm') da grade pública de um barbeiro e serviço na data. */
function grade(VinculoEstabelecimento $vinculo, Servico $servico, string $data): array
{
    return test()->getJson("/api/barbearias/{$vinculo->estabelecimento_id}/disponibilidade?servico_id={$servico->id}&vinculo_id={$vinculo->id}&data={$data}")
        ->assertOk()
        ->json('horarios.*.hora');
}
