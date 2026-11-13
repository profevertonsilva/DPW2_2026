<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 11. Cadastro do cliente em cada estabelecimento.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_estabelecimento', function (Blueprint $table) {
            $table->foreignId('cliente_id');
            $table->foreignId('estabelecimento_id');
            $table->foreign('cliente_id', 'fk_cliente_estabelecimento_cliente')->references('id')->on('cliente');
            $table->foreign('estabelecimento_id', 'fk_cliente_estabelecimento_local')->references('id')->on('estabelecimento');
            $table->index('estabelecimento_id', 'idx_cliente_estabelecimento_local');
        });

        DB::statement('ALTER TABLE cliente_estabelecimento ADD CONSTRAINT pk_cliente_estabelecimento PRIMARY KEY (cliente_id, estabelecimento_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_estabelecimento');
    }
};
