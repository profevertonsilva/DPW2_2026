<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 12. Períodos bloqueados na agenda de um vínculo (RF #14).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueio_horario', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('vinculo_estabelecimento_id');
            $table->timestampTz('inicio');
            $table->timestampTz('fim');
            $table->text('motivo')->nullable();
            $table->timestampTz('criado_em')->useCurrent();
            $table->foreign('vinculo_estabelecimento_id', 'fk_bloqueio_vinculo')->references('id')->on('vinculo_estabelecimento');
            $table->index(['vinculo_estabelecimento_id', 'inicio'], 'idx_bloqueio_vinculo_inicio');
        });

        DB::statement('ALTER TABLE bloqueio_horario ADD CONSTRAINT pk_bloqueio_horario PRIMARY KEY (id)');

        DB::statement('ALTER TABLE bloqueio_horario ADD CONSTRAINT ck_bloqueio_periodo CHECK (fim > inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueio_horario');
    }
};
