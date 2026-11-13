<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 14. Agendamento/atendimento de um cliente por um profissional.
// Correção 5.7: valores de status sem acento nem espaço.
// Correção 5.1 (RF #36, RNF #01): restrição de exclusão que impede, no banco,
// dois atendimentos ativos sobrepostos para o mesmo vínculo. Intervalo
// semiaberto '[)': 10:00-10:30 e 10:30-11:00 não conflitam.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::create('atendimento', function (Blueprint $table) {
            $table->bigInteger('id')->generatedAs();
            $table->foreignId('cliente_id');
            $table->foreignId('estabelecimento_id');
            $table->foreignId('vinculo_estabelecimento_id');
            $table->timestampTz('inicio');
            $table->timestampTz('fim');
            $table->decimal('valor_final', 10, 2)->nullable();
            $table->timestampTz('reserva_expira_em')->nullable();
            $table->timestampTz('presenca_confirmada_em')->nullable();
            $table->timestampTz('cancelada_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->timestampTz('criado_em')->useCurrent();
            $table->timestampTz('atualizado_em')->useCurrent();
            $table->string('status', 50)->default('reservado');
            $table->foreign('estabelecimento_id', 'fk_atendimento_estabelecimento')->references('id')->on('estabelecimento');
            $table->foreign(['cliente_id', 'estabelecimento_id'], 'fk_atendimento_cliente_local')
                ->references(['cliente_id', 'estabelecimento_id'])->on('cliente_estabelecimento');
            $table->foreign(['vinculo_estabelecimento_id', 'estabelecimento_id'], 'fk_atendimento_vinculo_local')
                ->references(['id', 'estabelecimento_id'])->on('vinculo_estabelecimento');
            $table->index(['estabelecimento_id', 'inicio'], 'idx_atendimento_estabelecimento_inicio');
            $table->index(['cliente_id', 'estabelecimento_id'], 'idx_atendimento_cliente_local');
            $table->index(['vinculo_estabelecimento_id', 'estabelecimento_id'], 'idx_atendimento_vinculo_local');
        });

        DB::statement('ALTER TABLE atendimento ADD CONSTRAINT pk_atendimento PRIMARY KEY (id)');

        DB::statement('ALTER TABLE atendimento ADD CONSTRAINT ck_atendimento_periodo CHECK (fim > inicio)');
        DB::statement('ALTER TABLE atendimento ADD CONSTRAINT ck_atendimento_valor CHECK (valor_final >= 0)');
        DB::statement("ALTER TABLE atendimento ADD CONSTRAINT ck_atendimento_status CHECK (status IN (
            'reservado', 'agendado', 'aguardando_confirmacao', 'confirmado',
            'realizado', 'cancelado', 'expirado', 'nao_compareceu'
        ))");
        DB::statement("ALTER TABLE atendimento
            ADD CONSTRAINT ex_atendimento_sem_sobreposicao
            EXCLUDE USING gist (
                vinculo_estabelecimento_id WITH =,
                tstzrange(inicio, fim, '[)') WITH &&
            )
            WHERE (status IN ('reservado', 'agendado', 'aguardando_confirmacao', 'confirmado', 'realizado'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('atendimento');
    }
};
