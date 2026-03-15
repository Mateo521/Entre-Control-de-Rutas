<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Note;
use Carbon\Carbon;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        
        Note::create([
            'title' => 'Renovar seguro de la retroexcavadora',
            'description' => 'La póliza venció, urgente contactar a la aseguradora.',
            'due_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'is_completed' => false,
            'is_archived' => false,
        ]);

     
        Note::create([
            'title' => 'Auditoría de insumos - Peaje Norte',
            'description' => 'Preparar inventario para la visita del supervisor.',
            'due_date' => Carbon::now()->addDays(1)->format('Y-m-d'),
            'is_completed' => false,
            'is_archived' => false,
        ]);

        
        Note::create([
            'title' => 'Reunión mensual de cuadrillas',
            'description' => 'Planificación de trabajos de asfalto para el próximo mes.',
            'due_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'is_completed' => false,
            'is_archived' => false,
        ]);

      
        Note::create([
            'title' => 'Comprar nuevos conos reflectivos',
            'description' => 'Hacer el pedido a compras cuando haya presupuesto.',
            'due_date' => null,
            'is_completed' => false,
            'is_archived' => false,
        ]);
    }
}