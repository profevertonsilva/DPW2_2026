<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 07. Dados e localização do estabelecimento.
// Correção 5.6: sem PostGIS; `localizacao GEOGRAPHY` e seu índice GiST foram
// substituídos por latitude e longitude opcionais.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estabelecimento', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->foreignId('categoria_estabelecimento_id');
            $table->string('cep', 9)->nullable();
            $table->char('estado', 2)->nullable();
            $table->string('cidade', 50)->nullable();
            $table->string('bairro', 50)->nullable();
            $table->string('logradouro', 100)->nullable();
            $table->integer('numero')->nullable();
            $table->string('complemento', 255)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->char('status', 1)->default('a');
            $table->integer('antecedencia_lembrete_minutos')->nullable();
            $table->boolean('cobranca_antecipada')->default(false);
            $table->foreign('categoria_estabelecimento_id', 'fk_estabelecimento_categoria')
                ->references('id')->on('categoria_estabelecimento');
            $table->index('categoria_estabelecimento_id', 'idx_estabelecimento_categoria');
        });

        DB::statement('ALTER TABLE estabelecimento ADD CONSTRAINT pk_estabelecimento PRIMARY KEY (id)');

        DB::statement("ALTER TABLE estabelecimento ADD CONSTRAINT ck_estabelecimento_status CHECK (status IN ('a', 'i'))");
        DB::statement('ALTER TABLE estabelecimento ADD CONSTRAINT ck_estabelecimento_lembrete CHECK (antecedencia_lembrete_minutos >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('estabelecimento');
    }
};
