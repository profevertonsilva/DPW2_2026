<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabela nova (correção 5.5, RF #13): faixas de trabalho de cada barbeiro por
// dia da semana (0 = domingo a 6 = sábado). Pode haver mais de uma faixa no dia.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jornada', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('vinculo_estabelecimento_id');
            $table->smallInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->foreign('vinculo_estabelecimento_id', 'fk_jornada_vinculo')->references('id')->on('vinculo_estabelecimento');
            $table->index(['vinculo_estabelecimento_id', 'dia_semana'], 'idx_jornada_vinculo_dia');
        });

        DB::statement('ALTER TABLE jornada ADD CONSTRAINT pk_jornada PRIMARY KEY (id)');

        DB::statement('ALTER TABLE jornada ADD CONSTRAINT ck_jornada_dia_semana CHECK (dia_semana BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE jornada ADD CONSTRAINT ck_jornada_periodo CHECK (hora_fim > hora_inicio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('jornada');
    }
};
