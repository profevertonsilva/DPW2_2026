<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 03. Atribuição de papéis de plataforma (RF #01, fora do escopo; mantida sem uso).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_papel', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('login_id');
            $table->foreignId('papel_plataforma_id');
            $table->timestampTz('atribuido_em')->useCurrent();
            $table->string('status', 15);
            $table->foreignId('atribuido_por')->nullable();
            $table->unique(['login_id', 'papel_plataforma_id'], 'uq_login_papel');
            $table->foreign('login_id', 'fk_login_papel_login')->references('id')->on('login');
            $table->foreign('papel_plataforma_id', 'fk_login_papel_papel')->references('id')->on('papel_plataforma');
            $table->foreign('atribuido_por', 'fk_login_papel_autor')->references('id')->on('login');
            $table->index('papel_plataforma_id', 'idx_login_papel_papel');
            $table->index('atribuido_por', 'idx_login_papel_autor');
        });

        DB::statement('ALTER TABLE login_papel ADD CONSTRAINT pk_login_papel PRIMARY KEY (id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('login_papel');
    }
};
