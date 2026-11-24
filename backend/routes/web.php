<?php

use Illuminate\Support\Facades\Route;

// O AgendAki é só API (routes/api.php); a raiz aponta para a prova de vida.
Route::get('/', fn () => response()->json([
    'aplicacao' => 'AgendAki API',
    'saude' => url('/api/health'),
    'documentacao' => 'docs/api.http no repositório',
]));
