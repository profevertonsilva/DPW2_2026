<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 18. Histórico de operações (RF #38).
// Correção 5.8: usuario_responsavel referencia login (o cliente também cancela),
// coluna `entidade` com o nome da tabela afetada, valores em JSONB.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('usuario_responsavel');
            $table->foreignId('estabelecimento_id');
            $table->string('tipo_operacao', 30);
            $table->bigInteger('entidade_afetada');
            $table->jsonb('valor_anterior')->nullable();
            $table->jsonb('valor_novo')->nullable();
            $table->timestampTz('data_hora')->useCurrent();
            $table->string('entidade', 50);
            $table->foreign('usuario_responsavel', 'fk_auditoria_responsavel')->references('id')->on('login');
            $table->foreign('estabelecimento_id', 'fk_auditoria_estabelecimento')->references('id')->on('estabelecimento');
            $table->index('usuario_responsavel', 'idx_auditoria_responsavel');
            $table->index(['estabelecimento_id', 'data_hora'], 'idx_auditoria_estabelecimento_data');
        });

        DB::statement('ALTER TABLE auditoria ADD CONSTRAINT pk_auditoria PRIMARY KEY (id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
