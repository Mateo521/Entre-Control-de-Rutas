<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractorWork;
use Carbon\Carbon;

class ContractorWorkSeeder extends Seeder
{
    public function run(): void
    {
        $trabajos = [
            [
                'company_name' => 'Servicios Viales Puntanos SRL',
                'work_type' => 'Corte de pasto',
                'location' => 'Autopista 55 (Norte) - Km 750 al 800',
                'description' => 'Desmalezado de banquinas y cantero central. Uso de tractores y motoguadañas.',
                'status' => 'En progreso',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'company_name' => 'Espacios Verdes San Luis',
                'work_type' => 'Poda correctiva',
                'location' => 'Ruta Provincial 20 - Cruz de Piedra a El Volcán',
                'description' => 'Despeje de luminarias y señalética vertical obstruida por arboleda.',
                'status' => 'Finalizado',
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'company_name' => 'Mantenimiento Vial Cono Sur',
                'work_type' => 'Pintura',
                'location' => 'Puente Río Quinto (Autopista Serranías Puntanas)',
                'description' => 'Pintado de barandas laterales y demarcación de pilares (Blanco y Amarillo reflectivo).',
                'status' => 'Certificado para pago',
                'created_at' => Carbon::now()->subDays(15),
            ],
            [
                'company_name' => 'Servicios Viales Puntanos SRL',
                'work_type' => 'Corte de pasto',
                'location' => 'Ruta 30 - Tramo Peaje a La Punilla',
                'description' => 'Avance demorado por lluvias en la zona. Retoman mañana.',
                'status' => 'En progreso',
                'created_at' => Carbon::now()->subHours(12),
            ],
            [
                'company_name' => 'Infraestructura Cuyo SA',
                'work_type' => 'Mantenimiento general',
                'location' => 'Peaje Desaguadero (Ambos sentidos)',
                'description' => 'Limpieza profunda de canaletas de desagüe y recambio de cartelería dañada por viento.',
                'status' => 'Finalizado',
                'created_at' => Carbon::now()->subDays(3),
            ]
        ];

        foreach ($trabajos as $trabajo) {
            ContractorWork::create($trabajo);
        }
    }
}