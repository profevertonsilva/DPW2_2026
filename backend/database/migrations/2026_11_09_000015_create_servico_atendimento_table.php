<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 15. Serviços de um atendimento e o preço praticado no momento do agendamento.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servico_atendimento', function (Blueprint $table) {
            $table->foreignId('atendimento_id');
            $table->foreignId('servico_id');
            $table->decimal('valor', 10, 2);
            $table->foreign('atendimento_id', 'fk_servico_atendimento_atendimento')->references('id')->on('atendimento');
            $table->foreign('servico_id', 'fk_servico_atendimento_servico')->references('id')->on('servico');
            $table->index('servico_id', 'idx_servico_atendimento_servico');
        });

        DB::statement('ALTER TABLE servico_atendimento ADD CONSTRAINT pk_servico_atendimento PRIMARY KEY (atendimento_id, servico_id)');

        DB::statement('ALTER TABLE servico_atendimento ADD CONSTRAINT ck_servico_atendimento_valor CHECK (valor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servico_atendimento');
    }
};
