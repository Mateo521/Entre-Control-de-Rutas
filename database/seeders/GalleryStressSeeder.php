<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractorWork;
use Carbon\Carbon;

class GalleryStressSeeder extends Seeder
{
    public function run(): void
    {
      
        for ($i = 1; $i <= 60; $i++) {
            ContractorWork::create([
                'company_name' => "Constructora Vial $i S.A.",
                'work_type' => 'Mantenimiento general',
                'location' => "Autopista 55 - Km " . (700 + $i),
                'description' => "Registro de prueba autogenerado número $i para auditar el rendimiento del scroll infinito en el portafolio de evidencias.",
                'status' => 'Finalizado',
                
                'media_paths' => [
                    "https://picsum.photos/seed/prueba_A_{$i}/800/600.jpg",
                    "https://picsum.photos/seed/prueba_B_{$i}/800/600.jpg"
                ],
          
                'created_at' => Carbon::now()->subHours($i * 2),
                'updated_at' => Carbon::now()->subHours($i * 2),
            ]);
        }
    }
}