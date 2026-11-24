<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 16. Associação denominada pacote no DER (mantida sem uso, seção 5.11).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacote', function (Blueprint $table) {
            $table->foreignId('atendimento_id');
            $table->foreignId('servico_id');
            $table->decimal('valor', 10, 2);
            $table->foreign('atendimento_id', 'fk_pacote_atendimento')->references('id')->on('atendimento');
            $table->foreign('servico_id', 'fk_pacote_servico')->references('id')->on('servico');
            $table->index('servico_id', 'idx_pacote_servico');
        });

        DB::statement('ALTER TABLE pacote ADD CONSTRAINT pk_pacote PRIMARY KEY (atendimento_id, servico_id)');

        DB::statement('ALTER TABLE pacote ADD CONSTRAINT ck_pacote_valor CHECK (valor >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('pacote');
    }
};
