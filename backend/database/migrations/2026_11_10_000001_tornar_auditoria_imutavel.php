<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// RF #38: a trilha de auditoria não é editável. Além de a API não oferecer
// edição nem exclusão, o banco recusa UPDATE e DELETE na tabela.
// "OR REPLACE": o migrate:fresh apaga as tabelas, mas não as funções.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_auditoria_imutavel() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'A trilha de auditoria não pode ser alterada nem excluída (RF #38).';
            END;
            $$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER tg_auditoria_imutavel
            BEFORE UPDATE OR DELETE ON auditoria
            FOR EACH ROW EXECUTE FUNCTION fn_auditoria_imutavel()
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS tg_auditoria_imutavel ON auditoria');
        DB::statement('DROP FUNCTION IF EXISTS fn_auditoria_imutavel()');
    }
};
