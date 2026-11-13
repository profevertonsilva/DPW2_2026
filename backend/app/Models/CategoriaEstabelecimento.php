<?php

namespace App\Models;

class CategoriaEstabelecimento extends Modelo
{
    protected $table = 'categoria_estabelecimento';

    const CREATED_AT = 'criado_em';

    const UPDATED_AT = 'atualizado_em';

    /** Categoria única do AgendAki, criada por seeder e oculta na interface. */
    const BARBEARIA = 'Barbearia';
}
