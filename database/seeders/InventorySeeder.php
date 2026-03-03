<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\User;

class InventorySeeder extends Seeder
{
    public function run()
    {
         
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],  
            ['name' => 'Administrador', 'password' => bcrypt('Admin1234!')]
        );

        $catalogo = [
          
            ['name' => 'Material en frío (Granel)', 'category' => 'Bacheo', 'unit_measure' => 'Toneladas', 'alert' => 10, 'initial' => 50],
            ['name' => 'Bolsas Material en frío', 'category' => 'Bacheo', 'unit_measure' => 'Unidades', 'alert' => 50, 'initial' => 300],
            
           
            ['name' => 'Hojas de guardarrail', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 20, 'initial' => 150],
            ['name' => 'Tornillos', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 500, 'initial' => 3000],
            ['name' => 'Punteras', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 10, 'initial' => 40],
            ['name' => 'Cebras', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 20, 'initial' => 80],
            ['name' => 'Chapitas reflectivas', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 100, 'initial' => 500],
            ['name' => 'Patas (postes)', 'category' => 'Guardarrail', 'unit_measure' => 'Unidades', 'alert' => 30, 'initial' => 150],
        ];

        foreach ($catalogo as $item) {
         
            $inventoryItem = InventoryItem::create([
                'name' => $item['name'],
                'category' => $item['category'],
                'unit_measure' => $item['unit_measure'],
                'alert_threshold' => $item['alert'],
                'current_stock' => 0
            ]);

        
            InventoryMovement::create([
                'inventory_item_id' => $inventoryItem->id,
                'user_id' => $admin->id,
                'type' => 'in',
                'quantity' => $item['initial'],
                'reference_document' => 'OP-' . rand(1000, 9999),
                'description' => 'Carga inicial de sistema por inventario físico.'
            ]);

         
            $inventoryItem->update([
                'current_stock' => $item['initial']
            ]);
        }
    }
}