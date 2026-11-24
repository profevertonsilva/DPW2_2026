<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 05. Perfil de cliente.
// Correção 5.3: no máximo um cliente por login (UNIQUE em login_id,
// que substitui o índice simples idx_cliente_login).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->string('nome', 150);
            $table->string('telefone', 15)->nullable();
            $table->string('email', 150);
            $table->string('cpf', 14)->nullable();
            $table->string('foto_perfil', 255)->nullable();
            $table->integer('recorrencia')->nullable();
            $table->foreignId('login_id');
            $table->foreign('login_id', 'fk_cliente_login')->references('id')->on('login');
            $table->unique('login_id', 'uq_cliente_login');
        });

        DB::statement('ALTER TABLE cliente ADD CONSTRAINT pk_cliente PRIMARY KEY (id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente');
    }
};
