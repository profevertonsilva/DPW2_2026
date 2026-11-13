<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 01. Credenciais e informações de acesso.
// Correção 5.3: e-mail como credencial, único e em minúsculas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->timestampTz('data_criacao')->useCurrent();
            $table->timestampTz('ultimo_acesso')->nullable();
            $table->text('senha_hash');
            $table->string('tipo_usuario', 100);
            $table->char('status', 1)->default('a');
            $table->timestampTz('data_atualizacao')->useCurrent();
            $table->string('email', 150);
            $table->unique('email', 'uq_login_email');
        });

        DB::statement('ALTER TABLE login ADD CONSTRAINT pk_login PRIMARY KEY (id)');

        DB::statement("ALTER TABLE login ADD CONSTRAINT ck_login_status CHECK (status IN ('a', 'i'))");
        DB::statement('ALTER TABLE login ADD CONSTRAINT ck_login_email_minusculas CHECK (email = lower(email))');
    }

    public function down(): void
    {
        Schema::dropIfExists('login');
    }
};
