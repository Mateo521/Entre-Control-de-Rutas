<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Toll;
use App\Models\Incident;
use App\Models\Action;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SystemDataSeeder extends Seeder
{
    public function run()
    {
    
        DB::statement('TRUNCATE TABLE actions, incidents, tolls, users RESTART IDENTITY CASCADE;');

        
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@enterutas.com',
            'password' => Hash::make('password123'),
        ]);

        $schemaBase = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ]; 

        
        $tolls = [
            Toll::create(['name' => 'Peaje La Cumbre', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Desaguadero Este', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Desaguadero Oeste', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Los Puquios', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Cruz de Piedra', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Perilago', 'dynamic_schema' => $schemaBase]),
            Toll::create(['name' => 'Peaje Ruta 30', 'dynamic_schema' => $schemaBase]),
        ];

        
        $casosReales = [
            ['toll_id' => 1, 'type' => 'accidente_vial', 'lat' => -33.4976835, 'lng' => -65.7934509, 'obs' => 'Colisión por alcance en Puente de Fraga (Km 731). Unidades de emergencia en el lugar.', 'dias_atras' => 2],
            ['toll_id' => 1, 'type' => 'animal_ruta', 'lat' => -33.3989463, 'lng' => -65.9825863, 'obs' => 'Presencia de equinos sueltos cerca del Acceso a Dique Paso de Las Carretas (Km 751).', 'dias_atras' => 5],
            ['toll_id' => 2, 'type' => 'pesaje_excedido', 'lat' => -33.3753655, 'lng' => -66.6278656, 'obs' => 'Camión de carga pesada evadiendo control por Salinas del Bebedero (Km 815).', 'dias_atras' => 0],
            ['toll_id' => 4, 'type' => 'falla_infraestructura', 'lat' => -33.1028936, 'lng' => -66.0585147, 'obs' => 'Desprendimiento de rocas en Rotonda 7 Cajones (Km 39.7). Banquina obstruida.', 'dias_atras' => 1],
            ['toll_id' => 4, 'type' => 'corte_ruta', 'lat' => -32.8185007, 'lng' => -66.0957608, 'obs' => 'Corte parcial por evento climático (Nieve) en La Carolina (Km 79).', 'dias_atras' => 12],
            ['toll_id' => 5, 'type' => 'fuga_peaje', 'lat' => -33.2847671, 'lng' => -66.2497884, 'obs' => 'Vehículo evade control a alta velocidad sentido Ave Fenix (Km 9).', 'dias_atras' => 3],
            ['toll_id' => 6, 'type' => 'accidente_vial', 'lat' => -33.2415744, 'lng' => -65.8955251, 'obs' => 'Siniestro en Puente Río 5to (Km 46). Tránsito asistido.', 'dias_atras' => 8]
        ];

        foreach ($casosReales as $caso) {
            $fechaIncidente = Carbon::now()->subDays($caso['dias_atras'])->subHours(rand(1, 12));
            
            $incident = Incident::create([
                'toll_id' => $caso['toll_id'],
                'user_id' => 1, 
                'incident_type' => $caso['type'],
                'dynamic_data' => [
                    'latitud' => $caso['lat'],
                    'longitud' => $caso['lng'],
                    'observaciones_mapa' => $caso['obs'],
                    'estado_climatico' => 'Despejado',
                    'gravedad' => 'Alta'
                ],
                'status' => 'pending',
                'created_at' => clone $fechaIncidente
            ]);

            
            if (in_array($caso['type'], ['accidente_vial', 'animal_ruta', 'falla_infraestructura'])) {
                Action::create([
                    'toll_id' => $caso['toll_id'],
                    'category' => 'Despliegue Operativo',
                    'title' => 'Asistencia de Móvil en Ruta',
                    'description' => 'Móvil despachado al kilómetro referenciado para asegurar la zona. Tiempo de respuesta estimado: ' . rand(10, 45) . ' minutos.',
                    'media_paths' => [],
                    'created_at' => (clone $fechaIncidente)->addMinutes(10)
                ]);
            }
        }

        
        $tipos = ['pesaje_excedido', 'fuga_peaje', 'animal_ruta', 'accidente_vial', 'falla_infraestructura', 'corte_ruta'];
        
        for ($i = 0; $i < 45; $i++) {
            $randomToll = $tolls[array_rand($tolls)];
            $randomType = $tipos[array_rand($tipos)];
            
            Incident::create([
                'toll_id' => $randomToll->id,
                'user_id' => 1,
                'incident_type' => $randomType,
                'dynamic_data' => [
                    'observaciones_mapa' => 'Registro de auditoría automática generado por el sistema central.',
                    'velocidad_estimada' => rand(60, 130) . ' km/h',
                    'requiere_mantenimiento' => rand(0, 1) ? true : false
                ],
                'status' => rand(0, 1) ? 'resolved' : 'pending',
                'created_at' => Carbon::now()->subDays(rand(0, 30))->subMinutes(rand(1, 1440))
            ]);
        }
    }
}