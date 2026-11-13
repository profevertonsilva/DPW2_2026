<?php

use App\Http\Middleware\VinculoNaBarbearia;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'vinculo' => VinculoNaBarbearia::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Mensagens de erro em português (seção 7 do briefing).
        $mensagem = fn (Throwable $e, string $padrao) => ($e->getMessage() === '' || str_starts_with($e->getMessage(), 'This action') || str_starts_with($e->getMessage(), 'No query results'))
            ? $padrao
            : $e->getMessage();

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Não autenticado. Faça login novamente.'], 401)
            : null);

        $exceptions->render(fn (AuthorizationException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $mensagem($e, 'Você não tem permissão para esta ação.')], 403)
            : null);

        $exceptions->render(fn (AccessDeniedHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $mensagem($e, 'Você não tem permissão para esta ação.')], 403)
            : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $mensagem($e, 'Recurso não encontrado.')], 404)
            : null);

        $exceptions->render(fn (MethodNotAllowedHttpException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Método não permitido nesta rota.'], 405)
            : null);

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => 'Muitas tentativas. Aguarde um instante e tente de novo.'], 429)
            : null);
    })->create();
