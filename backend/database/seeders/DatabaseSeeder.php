<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** `php artisan migrate:fresh --seed` recria tudo do zero. */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            DemonstracaoSeeder::class,
        ]);
    }
}
