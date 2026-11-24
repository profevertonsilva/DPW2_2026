<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 04. Perfil profissional.
// Correção 5.3: no máximo um profissional por login (UNIQUE em login_id,
// que substitui o índice simples idx_profissional_login).
// Pendência 5.10: status 'a'/'i', default 'a'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profissional', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->string('nome', 150);
            $table->string('telefone', 15)->nullable();
            $table->string('email', 150);
            $table->string('cpf', 14)->nullable();
            $table->char('status', 1)->default('a');
            $table->string('foto_perfil', 255)->nullable();
            $table->foreignId('login_id');
            $table->foreign('login_id', 'fk_profissional_login')->references('id')->on('login');
            $table->unique('login_id', 'uq_profissional_login');
        });

        DB::statement('ALTER TABLE profissional ADD CONSTRAINT pk_profissional PRIMARY KEY (id)');

        DB::statement("ALTER TABLE profissional ADD CONSTRAINT ck_profissional_status CHECK (status IN ('a', 'i'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('profissional');
    }
};
