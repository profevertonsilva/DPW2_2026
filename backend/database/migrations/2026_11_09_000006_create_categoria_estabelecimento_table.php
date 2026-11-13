<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 06. Categorias dos estabelecimentos. No AgendAki há uma só: "Barbearia".
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categoria_estabelecimento', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->char('status', 1)->default('a');
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
        });

        DB::statement('ALTER TABLE categoria_estabelecimento ADD CONSTRAINT pk_categoria_estabelecimento PRIMARY KEY (id)');

        DB::statement("ALTER TABLE categoria_estabelecimento ADD CONSTRAINT ck_categoria_status CHECK (status IN ('a', 'i'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_estabelecimento');
    }
};
