<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 09. Horários semanais de funcionamento (0 = domingo a 6 = sábado).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horario_funcionamento', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('estabelecimento_id');
            $table->smallInteger('dia_semana');
            $table->boolean('aberto');
            $table->time('hora_abertura')->nullable();
            $table->time('hora_fechamento')->nullable();
            $table->foreign('estabelecimento_id', 'fk_horario_estabelecimento')->references('id')->on('estabelecimento');
            $table->index(['estabelecimento_id', 'dia_semana'], 'idx_horario_estabelecimento_dia');
        });

        DB::statement('ALTER TABLE horario_funcionamento ADD CONSTRAINT pk_horario_funcionamento PRIMARY KEY (id)');

        DB::statement('ALTER TABLE horario_funcionamento ADD CONSTRAINT ck_horario_dia_semana CHECK (dia_semana BETWEEN 0 AND 6)');
        DB::statement('ALTER TABLE horario_funcionamento ADD CONSTRAINT ck_horario_aberto CHECK (NOT aberto OR (hora_abertura IS NOT NULL AND hora_fechamento IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('horario_funcionamento');
    }
};
