<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Action;
use App\Models\InventoryItem;
use App\Models\User;
use Carbon\Carbon;

class BacheoSeeder extends Seeder
{
    public function run(): void
    {
     
        $admin = User::first();
        if (!$admin) return;

         
    
        $asfaltoFrio = InventoryItem::firstOrCreate(
            ['name' => 'Asfalto en Frío (A Granel)'],
            ['category' => 'Bacheo', 'unit_measure' => 'Toneladas', 'current_stock' => 150.50, 'alert_threshold' => 20]
        );

        $emulsion = InventoryItem::firstOrCreate(
            ['name' => 'Emulsión Asfáltica (Tambores)'],
            ['category' => 'Bacheo', 'unit_measure' => 'Litros', 'current_stock' => 500, 'alert_threshold' => 100]
        );

        $bolsas = InventoryItem::firstOrCreate(
            ['name' => 'Bolsas de Bacheo Preparado'],
            ['category' => 'Bacheo', 'unit_measure' => 'Unidades', 'current_stock' => 300, 'alert_threshold' => 50]
        );

         
        $trabajos = [
            [
                'title' => 'Ruta 20 - Km 14 al 18',
                'description' => 'Reparación de 4 baches profundos en el carril derecho. Cuadrilla operativa turno mañana.',
                'created_at' => Carbon::now()->subDays(2),
                'materiales' => [
                    $asfaltoFrio->id => ['quantity' => 2.5],
                    $emulsion->id => ['quantity' => 15]
                ]
            ],
            [
                'title' => 'Autopista Serranías Puntanas - Km 760',
                'description' => 'Bacheo de emergencia por desprendimiento de calzada tras lluvias.',
                'created_at' => Carbon::now()->subDays(1),
                'materiales' => [
                    $bolsas->id => ['quantity' => 45],
                    $emulsion->id => ['quantity' => 10]
                ]
            ],
            [
                'title' => 'Ingreso Peaje La Cumbre',
                'description' => 'Acondicionamiento de zona de frenado previa a las barreras.',
                'created_at' => Carbon::now()->subHours(5),
                'materiales' => [
                    $asfaltoFrio->id => ['quantity' => 1.2]
                ]
            ]
        ];

  
        foreach ($trabajos as $trabajo) {
            $action = Action::create([
                'category' => 'Mantenimiento Vial - Bacheo',
                'title' => $trabajo['title'],
                'description' => $trabajo['description'],
                'created_at' => $trabajo['created_at'],
                'updated_at' => $trabajo['created_at'],
            ]);

            foreach ($trabajo['materiales'] as $itemId => $pivotData) {
              
                $action->inventoryItems()->attach($itemId, ['quantity_used' => $pivotData['quantity']]);
            }
        }
    }
}