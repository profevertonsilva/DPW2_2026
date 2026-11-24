<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 17. Avaliação opcional e única de cada atendimento (RF #30).
// Correção 5.9: nota de 1 a 5. Pendência 5.10: status 'a'/'i', default 'a'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacao', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('atendimento_id');
            $table->integer('nota');
            $table->text('comentario')->nullable();
            $table->string('status', 1)->default('a');
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->unique('atendimento_id', 'uq_avaliacao_atendimento');
            $table->foreign('atendimento_id', 'fk_avaliacao_atendimento')->references('id')->on('atendimento');
        });

        DB::statement('ALTER TABLE avaliacao ADD CONSTRAINT pk_avaliacao PRIMARY KEY (id)');

        DB::statement('ALTER TABLE avaliacao ADD CONSTRAINT ck_avaliacao_nota CHECK (nota BETWEEN 1 AND 5)');
        DB::statement("ALTER TABLE avaliacao ADD CONSTRAINT ck_avaliacao_status CHECK (status IN ('a', 'i'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacao');
    }
};
