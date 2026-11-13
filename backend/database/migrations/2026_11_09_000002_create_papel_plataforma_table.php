<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 02. Papéis disponíveis na plataforma (RF #01, fora do escopo; mantida sem uso).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papel_plataforma', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->string('nome', 30);
            $table->text('descricao')->nullable();
        });

        DB::statement('ALTER TABLE papel_plataforma ADD CONSTRAINT pk_papel_plataforma PRIMARY KEY (id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('papel_plataforma');
    }
};
