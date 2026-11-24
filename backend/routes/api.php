<?php

use App\Http\Controllers\AgendamentoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\BarbeariaController;
use App\Http\Controllers\Painel\AgendaController;
use App\Http\Controllers\Painel\AgendamentoManualController;
use App\Http\Controllers\Painel\AuditoriaController;
use App\Http\Controllers\Painel\BloqueioController;
use App\Http\Controllers\Painel\EquipeController;
use App\Http\Controllers\Painel\EstabelecimentoController;
use App\Http\Controllers\Painel\ServicoController;
use App\Http\Controllers\SenhaController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
| Rotas da API do AgendAki (seção 7 do briefing). Documentação executável em
| docs/api.http. Autenticação por token Bearer (Sanctum).
*/

// Prova de vida: estado da API e da conexão com o PostgreSQL.
Route::get('/health', function () {
    try {
        $banco = DB::selectOne('select current_database() as nome, version() as versao, now() as agora');

        return response()->json([
            'status' => 'ok',
            'banco' => [
                'conectado' => true,
                'nome' => $banco->nome,
                'versao' => $banco->versao,
                'horario' => $banco->agora,
            ],
        ]);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'status' => 'erro',
            'banco' => [
                'conectado' => false,
                'mensagem' => 'Não foi possível conectar ao banco de dados.',
            ],
        ], 503);
    }
});

// Públicas
Route::post('/auth/cadastro', [AuthController::class, 'cadastro'])->middleware('throttle:20,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/auth/esqueci-senha', [SenhaController::class, 'solicitar'])->middleware('throttle:5,1');
Route::post('/auth/redefinir-senha', [SenhaController::class, 'redefinir'])->middleware('throttle:10,1');

Route::get('/barbearias', [BarbeariaController::class, 'index']);
Route::get('/barbearias/{estabelecimento}', [BarbeariaController::class, 'show']);
Route::get('/barbearias/{estabelecimento}/disponibilidade', [BarbeariaController::class, 'disponibilidade']);
Route::get('/barbearias/{estabelecimento}/avaliacoes', [AvaliacaoController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'atualizarPerfil']);

    // Cliente
    Route::get('/agendamentos', [AgendamentoController::class, 'index']);
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);
    Route::get('/agendamentos/{atendimento}', [AgendamentoController::class, 'show']);
    Route::post('/agendamentos/{atendimento}/cancelar', [AgendamentoController::class, 'cancelar']);
    Route::post('/agendamentos/{atendimento}/remarcar', [AgendamentoController::class, 'remarcar']);
    Route::get('/agendamentos/{atendimento}/disponibilidade', [AgendamentoController::class, 'disponibilidade']);
    Route::put('/agendamentos/{atendimento}/avaliacao', [AvaliacaoController::class, 'salvar']);

    // Painel da barbearia
    Route::prefix('painel/estabelecimentos')->group(function () {
        Route::get('/', [EstabelecimentoController::class, 'index']);
        Route::post('/', [EstabelecimentoController::class, 'store']);

        // Isolamento (seção 6.4): vínculo ativo exigido e scoped bindings, de modo
        // que um recurso de outra barbearia resulta em 404.
        Route::prefix('{estabelecimento}')->scopeBindings()->group(function () {
            // Qualquer vínculo ativo (proprietário ou barbeiro)
            Route::middleware('vinculo')->group(function () {
                Route::get('/', [EstabelecimentoController::class, 'show']);
                Route::get('/agenda', [AgendaController::class, 'index']);
                Route::get('/agendamentos/{atendimento}', [AgendaController::class, 'show']);
                Route::post('/agendamentos/{atendimento}/realizado', [AgendaController::class, 'realizado']);
                Route::post('/agendamentos/{atendimento}/cancelar', [AgendaController::class, 'cancelar']);

                // Agendamento manual (tela 33): só para cliente já cadastrado.
                Route::get('/clientes', [AgendamentoManualController::class, 'clientes']);
                Route::post('/agendamentos', [AgendamentoManualController::class, 'store']);

                // Bloqueio de horários (RF #14): o barbeiro, só a própria agenda.
                Route::get('/bloqueios', [BloqueioController::class, 'index']);
                Route::get('/bloqueios/conflitos', [BloqueioController::class, 'conflitos']);
                Route::post('/bloqueios', [BloqueioController::class, 'store']);
                Route::delete('/bloqueios/{bloqueio}', [BloqueioController::class, 'destroy']);
            });

            // Lista da equipe: quem gere a agenda (proprietário, gerente e atendente).
            Route::get('/equipe', [EquipeController::class, 'index'])->middleware('vinculo:proprietario,gerente,atendente');

            // Gestão da barbearia, dos serviços e da equipe: proprietário e gerente (RF #03).
            Route::middleware('vinculo:proprietario,gerente')->group(function () {
                Route::put('/', [EstabelecimentoController::class, 'update']);

                Route::get('/servicos', [ServicoController::class, 'index']);
                Route::post('/servicos', [ServicoController::class, 'store']);
                Route::put('/servicos/{servico}', [ServicoController::class, 'update']);
                Route::delete('/servicos/{servico}', [ServicoController::class, 'destroy']);

                Route::post('/equipe', [EquipeController::class, 'store']);
                Route::delete('/equipe/{vinculo}', [EquipeController::class, 'destroy']);
                Route::put('/equipe/{vinculo}/papel', [EquipeController::class, 'papel']);
                Route::put('/equipe/{vinculo}/servicos', [EquipeController::class, 'servicos']);
                Route::put('/equipe/{vinculo}/jornada', [EquipeController::class, 'jornada']);
            });

            // Trilha de auditoria (RF #38): consulta do proprietário.
            Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('vinculo:proprietario');
        });
    });
});
