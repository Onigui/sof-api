<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $empresa = Empresa::create([
            'name' => 'Casa Senior (DEV)',
        ]);

        User::factory()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Operador Dev',
            'email' => 'operador@casa-senior.dev',
            'role' => User::ROLE_OPERADOR,
        ]);

        User::factory()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Analista Dev',
            'email' => 'analista@casa-senior.dev',
            'role' => User::ROLE_ANALISTA,
        ]);

        User::factory()->create([
            'empresa_id' => $empresa->id,
            'name' => 'Gestao Dev',
            'email' => 'gestao@casa-senior.dev',
            'role' => User::ROLE_GESTAO,
        ]);
    }
}
