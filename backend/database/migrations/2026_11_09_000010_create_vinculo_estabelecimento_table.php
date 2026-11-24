<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 10. Associação de profissionais com estabelecimentos.
// Correção 5.4: coluna `papel` e no máximo um vínculo ativo por
// profissional e estabelecimento (índice único parcial).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vinculo_estabelecimento', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('profissional_id');
            $table->foreignId('estabelecimento_id');
            $table->char('status', 1)->default('a');
            $table->timestampTz('vinculado_em')->useCurrent();
            $table->timestampTz('desvinculado_em')->nullable();
            $table->string('papel', 20);
            $table->unique(['id', 'estabelecimento_id'], 'uq_vinculo_id_estabelecimento');
            $table->foreign('profissional_id', 'fk_vinculo_profissional')->references('id')->on('profissional');
            $table->foreign('estabelecimento_id', 'fk_vinculo_estabelecimento')->references('id')->on('estabelecimento');
            $table->index('profissional_id', 'idx_vinculo_profissional');
            $table->index('estabelecimento_id', 'idx_vinculo_estabelecimento');
        });

        DB::statement('ALTER TABLE vinculo_estabelecimento ADD CONSTRAINT pk_vinculo_estabelecimento PRIMARY KEY (id)');

        DB::statement("ALTER TABLE vinculo_estabelecimento ADD CONSTRAINT ck_vinculo_status CHECK (status IN ('a', 'i'))");
        DB::statement('ALTER TABLE vinculo_estabelecimento ADD CONSTRAINT ck_vinculo_periodo CHECK (desvinculado_em >= vinculado_em)');
        DB::statement("ALTER TABLE vinculo_estabelecimento ADD CONSTRAINT ck_vinculo_papel CHECK (papel IN ('proprietario', 'gerente', 'atendente', 'profissional'))");
        DB::statement("CREATE UNIQUE INDEX uq_vinculo_ativo ON vinculo_estabelecimento (profissional_id, estabelecimento_id) WHERE status = 'a'");
    }

    public function down(): void
    {
        Schema::dropIfExists('vinculo_estabelecimento');
    }
};
