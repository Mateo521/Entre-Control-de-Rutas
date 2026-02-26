<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crea el usuario administrador por defecto
        User::updateOrCreate(
            ['email' => 'admin@enterutas.gov.ar'], // Busca por este email
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('Admin1234!'), // Contraseña encriptada
                // 'role' => 'admin' // Descomenta esta línea si agregaste la columna 'role' en tu migración de users
            ]
        );
    }
}