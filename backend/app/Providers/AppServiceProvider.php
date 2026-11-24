<?php

namespace App\Providers;

use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // TIMESTAMPTZ e TIME sem precisão explícita (microssegundos), como no
        // script da turma. O padrão do Laravel 13 é precisão 0.
        Builder::defaultTimePrecision(null);

        // Respostas sem o envelope "data": mais simples para o app e o painel.
        JsonResource::withoutWrapping();
    }
}
