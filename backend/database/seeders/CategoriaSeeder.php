<?php

namespace Database\Seeders;

use App\Models\CategoriaEstabelecimento;
use Illuminate\Database\Seeder;

/** Categoria única do AgendAki; a interface não exibe categoria. */
class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        CategoriaEstabelecimento::firstOrCreate(
            ['nome' => CategoriaEstabelecimento::BARBEARIA],
            ['descricao' => 'Barbearias', 'status' => 'a'],
        );
    }
}
