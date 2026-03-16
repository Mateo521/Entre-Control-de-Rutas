<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TowService;
use Carbon\Carbon;
use Faker\Factory as Faker;

class TowServiceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_AR');

        $this->command->info('Limpiando historial anterior de grúas...');
        DB::statement('TRUNCATE TABLE tow_services RESTART IDENTITY CASCADE;');

        $this->command->info('Generando 1500 registros de acarreo...');

        $gruas = ['Grúa Provincial', 'Grúa Desaguadero', 'Grúa La Cumbre'];
        $motivos = [
            'Desperfecto mecánico', 
            'Siniestro vial', 
            'Secuestro policial', 
            'Falta de combustible', 
            'Neumático averiado', 
            'Falla eléctrica',
            'Vehículo abandonado'
        ];

        $rutas = ['Ruta Nac. 7', 'Ruta Prov. 20', 'Ruta Prov. 9', 'Autopista Serranías Puntanas', 'Ruta Prov. 30'];

        $records = [];

        for ($i = 0; $i < 1500; $i++) {
         
            $fechaRandom = $faker->dateTimeBetween('-365 days', 'now');
            
         
            $patente = $faker->boolean(50) 
                ? strtoupper($faker->bothify('??###??')) 
                : strtoupper($faker->bothify('???###'));

            $records[] = [
                'tow_truck' => $faker->randomElement($gruas),
                'license_plate' => $patente,
                'location' => $faker->randomElement($rutas) . ' Km ' . $faker->numberBetween(10, 850),
                'reason' => $faker->randomElement($motivos),
                'observations' => $faker->boolean(40) ? $faker->sentence(8) : null,  
                'created_at' => $fechaRandom,
                'updated_at' => $fechaRandom,
              
                'deleted_at' => $faker->boolean(5) ? (clone $fechaRandom)->modify('+1 days') : null,
            ];
        }

       
        $chunks = array_chunk($records, 500);
        foreach ($chunks as $chunk) {
            TowService::insert($chunk);
        }

        $this->command->info('¡Simulación de acarreos completada exitosamente!');
    }
}