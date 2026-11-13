<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 13. Catálogo de serviços do estabelecimento.
// Correção 5.2: coluna `preco`, obrigatória e maior que zero.
// Pendência 5.10: status 'a'/'i', default 'a' ("excluir" no painel inativa).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servico', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('estabelecimento_id');
            $table->string('nome', 255);
            $table->text('descricao')->nullable();
            $table->integer('duracao_minutos');
            $table->char('status', 1)->default('a');
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->decimal('preco', 10, 2);
            $table->foreign('estabelecimento_id', 'fk_servico_estabelecimento')->references('id')->on('estabelecimento');
            $table->index('estabelecimento_id', 'idx_servico_estabelecimento');
        });

        DB::statement('ALTER TABLE servico ADD CONSTRAINT pk_servico PRIMARY KEY (id)');

        DB::statement('ALTER TABLE servico ADD CONSTRAINT ck_servico_duracao CHECK (duracao_minutos > 0)');
        DB::statement("ALTER TABLE servico ADD CONSTRAINT ck_servico_status CHECK (status IN ('a', 'i'))");
        DB::statement('ALTER TABLE servico ADD CONSTRAINT ck_servico_preco CHECK (preco > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('servico');
    }
};
