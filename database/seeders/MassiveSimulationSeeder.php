<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Toll;
use App\Models\Incident;
use App\Models\Action;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Note;
use App\Models\ContractorWork;
use App\Models\Weighing;
use Carbon\Carbon;
use Faker\Factory as Faker;

class MassiveSimulationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_AR');

        $this->command->info('Limpiando base de datos para la simulación masiva (Producción simulada)...');
     
        DB::statement('SET session_replication_role = replica;');  
        DB::statement('TRUNCATE TABLE notes, action_inventory, actions, incidents, inventory_movements, inventory_items, contractor_works, weighings, tolls, users RESTART IDENTITY CASCADE;');
        DB::statement('SET session_replication_role = DEFAULT;');

     
        $this->command->info('Creando usuario Administrador...');
        $admin = User::create([
            'name' => 'Administrador Ente',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('Admin1234!'),
        ]);

       
        $this->command->info('Configurando Peajes y Schemas...');
        $schemaGenerico = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ];

        $nombresPeajes = ['La Cumbre', 'Desaguadero Este', 'Desaguadero Oeste', 'Los Puquios', 'Cruz de Piedra', 'Perilago', 'Ruta 30'];
        $tolls = [];
        foreach ($nombresPeajes as $nombre) {
            $tolls[] = Toll::create([
                'name' => 'Peaje ' . $nombre,
                'dynamic_schema' => $schemaGenerico,
                'dynamic_data' => ['estado_barreras' => true, 'energia_red' => true, 'observaciones' => 'Operativo']
            ]);
        }

       
        $this->command->info('Cargando Inventario por Peaje e Historial de OP...');
        $categorias = ['Bacheo', 'Señalización', 'Guardarrail', 'Mantenimiento'];
        $medidas = ['Litros', 'Kilos', 'Unidades', 'Bolsas'];

        $inventoryItems = [];
        foreach ($tolls as $toll) {
            for ($i = 0; $i < 10; $i++) {
                $stockInicial = $faker->numberBetween(100, 2000);
                
                $item = InventoryItem::create([
                    'toll_id' => $toll->id,
                    'name' => $faker->words(3, true),
                    'category' => $faker->randomElement($categorias),
                    'unit_measure' => $faker->randomElement($medidas),
                    'alert_threshold' => $faker->numberBetween(10, 50),
                    'current_stock' => $stockInicial, 
                ]);
                $inventoryItems[] = $item;

               
                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'user_id' => $admin->id,
                    'type' => 'in',
                    'quantity' => $stockInicial,
                    'reference_document' => 'OP-' . $faker->numberBetween(1000, 9999),
                    'description' => 'Carga inicial de sistema (Stock Histórico)'
                ]);
            }
        }

        
        $this->command->info('Generando 2000 Incidentes (1 año de reportes viales)...');
        $tiposIncidentes = ['accidente_vial', 'animal_ruta', 'pesaje_excedido', 'fuga_peaje', 'falla_infraestructura', 'corte_ruta'];
        $incidents = [];
        for ($i = 0; $i < 2000; $i++) {
            $fechaRandom = $faker->dateTimeBetween('-400 days', 'now');
            $incidents[] = [
                'toll_id' => $faker->randomElement($tolls)->id,
                'user_id' => $admin->id,
                'incident_type' => $faker->randomElement($tiposIncidentes),
                'dynamic_data' => json_encode([
                    'latitud' => $faker->latitude(-34, -32),
                    'longitud' => $faker->longitude(-67, -64),
                    'observaciones_mapa' => $faker->sentence(10)
                ]),
                'media_paths' => json_encode([]),  
                'status' => $faker->boolean(90) ? 'resolved' : 'pending', 
                'created_at' => $fechaRandom,
                'updated_at' => $fechaRandom,
                'deleted_at' => $faker->boolean(70) ? (clone $fechaRandom)->modify('+5 days') : null,  
            ];
        }
        Incident::insert($incidents);

      
        $this->command->info('Generando 1500 Trabajos de Cuadrilla (Bacheo) consumiendo stock...');
        for ($i = 0; $i < 1500; $i++) {
            $fechaRandom = Carbon::instance($faker->dateTimeBetween('-400 days', 'now'));
            $toll = $faker->randomElement($tolls);

            $action = Action::create([
                'toll_id' => $toll->id,
                'category' => 'Mantenimiento Vial - Bacheo',
                'title' => 'Reparación en Ruta ' . $faker->numberBetween(1, 40) . ' Km ' . $faker->numberBetween(1, 900),
                'description' => $faker->paragraph(2),
                'media_paths' => [],
                'created_at' => $fechaRandom,
                'updated_at' => $fechaRandom,
            ]);

         
            $insumosDelPeaje = collect($inventoryItems)->where('toll_id', $toll->id);
            if ($insumosDelPeaje->isNotEmpty()) {
                $insumosAUsar = $insumosDelPeaje->random(min(2, $insumosDelPeaje->count()));
                
                foreach ($insumosAUsar as $insumo) {
                    $cantidadUsada = $faker->randomFloat(2, 1, 30);
                    
                   
                    $action->inventoryItems()->attach($insumo->id, [
                        'quantity_used' => $cantidadUsada
                    ]);

              
                    $insumo->decrement('current_stock', $cantidadUsada);
                }
            }
        }

       
        $this->command->info('Generando 500 Auditorías de Empresas Contratistas...');
        $contractorWorks = [];
        $empresas = ['Servicios Viales SRL', 'Pasteros del Sur SA', 'Construcciones Puntanas', 'Señalética Integral'];
        $tiposTrabajo = ['Corte de pasto', 'Poda correctiva', 'Pintura', 'Mantenimiento general'];
        
        for ($i = 0; $i < 500; $i++) {
            $fechaRandom = $faker->dateTimeBetween('-400 days', 'now');
            $contractorWorks[] = [
                'company_name' => $faker->randomElement($empresas),
                'work_type' => $faker->randomElement($tiposTrabajo),
                'location' => 'Autopista ' . $faker->numberBetween(55, 90) . ' Km ' . $faker->numberBetween(100, 800),
                'description' => $faker->sentence(15),
                'status' => $faker->randomElement(['En progreso', 'Finalizado', 'Certificado para pago']),
                'media_paths' => json_encode([]),
                'created_at' => $fechaRandom,
                'updated_at' => $fechaRandom,
                'deleted_at' => $faker->boolean(40) ? (clone $fechaRandom)->modify('+30 days') : null,
            ];
        }
        ContractorWork::insert($contractorWorks);

      
        $this->command->info('Generando 3000 Controles de Pesaje...');
        $weighings = [];
        for ($i = 0; $i < 3000; $i++) {
            $fechaRandom = $faker->dateTimeBetween('-400 days', 'now');
            $weighings[] = [
                'toll_id' => $faker->randomElement($tolls)->id,
                'license_plate' => strtoupper($faker->bothify('???###')),  
                'driver_dni' => $faker->numerify('########'),
                'weight_kg' => $faker->numberBetween(10000, 55000),  
                'created_at' => $fechaRandom,
                'updated_at' => $fechaRandom,
                'deleted_at' => $faker->boolean(20) ? (clone $fechaRandom)->modify('+10 days') : null,
            ];
        }
        Weighing::insert($weighings);

     
        $this->command->info('Generando 200 Notas de Mantenimiento Preventivo...');
        $notes = [];
        for ($i = 0; $i < 200; $i++) {
            $notes[] = [
                'title' => $faker->sentence(4),
                'description' => $faker->text(80),
                'due_date' => $faker->boolean(80) ? $faker->dateTimeBetween('-20 days', '+20 days') : null,
                'is_completed' => $faker->boolean(50),
                'is_archived' => $faker->boolean(30),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Note::insert($notes);

        $this->command->info('¡Simulación completada con éxito! Sistema listo para pruebas de estrés.');
    }
}