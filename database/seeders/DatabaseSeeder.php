<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
     
        User::updateOrCreate(
            ['email' => 'admin@enterutas.gov.ar'],  
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('Admin1234!'),  
                
            ]
        );
    }
}