<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 08. Telefones do estabelecimento.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telefone', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('estabelecimento_id');
            $table->string('telefone', 15);
            $table->foreign('estabelecimento_id', 'fk_telefone_estabelecimento')->references('id')->on('estabelecimento');
            $table->index('estabelecimento_id', 'idx_telefone_estabelecimento');
        });

        DB::statement('ALTER TABLE telefone ADD CONSTRAINT pk_telefone PRIMARY KEY (id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('telefone');
    }
};
