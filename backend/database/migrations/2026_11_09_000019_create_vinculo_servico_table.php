<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Tabela nova (correção 5.5, RF #13): serviços que cada barbeiro executa.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinculo_servico', function (Blueprint $table) {
            $table->foreignId('vinculo_estabelecimento_id');
            $table->foreignId('servico_id');
            $table->foreign('vinculo_estabelecimento_id', 'fk_vinculo_servico_vinculo')->references('id')->on('vinculo_estabelecimento');
            $table->foreign('servico_id', 'fk_vinculo_servico_servico')->references('id')->on('servico');
            $table->index('servico_id', 'idx_vinculo_servico_servico');
        });

        DB::statement('ALTER TABLE vinculo_servico ADD CONSTRAINT pk_vinculo_servico PRIMARY KEY (vinculo_estabelecimento_id, servico_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculo_servico');
    }
};
