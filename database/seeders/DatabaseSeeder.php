<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Toll;

class DatabaseSeeder extends Seeder
{

/*
    public function run(): void
    {
      
        $this->call(SystemDataSeeder::class);
        
     
        $this->call(InventorySeeder::class);
        
    
        $this->call(BacheoSeeder::class);
        
      
        $this->call(ContractorWorkSeeder::class);

         $this->call(ContractorWorkSeeder::class);
    }

    */

   public function run(): void
    {
        $this->command->info('Preparando el sistema para Producción...');

       
        $this->command->info('Vaciando carpetas de almacenamiento (Sucesos, Acciones, Contratistas, Peajes)...');
        Storage::disk('public')->deleteDirectory('peajes');
        Storage::disk('public')->deleteDirectory('acciones');
        Storage::disk('public')->deleteDirectory('sucesos');
        Storage::disk('public')->deleteDirectory('pasteros');

       
        Storage::disk('public')->makeDirectory('peajes');
        Storage::disk('public')->makeDirectory('acciones');
        Storage::disk('public')->makeDirectory('sucesos');
        Storage::disk('public')->makeDirectory('pasteros');

  
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('Admin1234!'),
        ]);

       
        $this->command->info('Configurando esquemas dinámicos y balanzas...');
        
        $schemaGenerico = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ];

        $schemaLaCumbre = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "balanza_ascendente", "type" => "booleano", "label" => "Balanza Ascendente (Mano a Mendoza)"],
                ["name" => "balanza_descendente", "type" => "booleano", "label" => "Balanza Descendente (Mano a Bs As)"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ];

        $schemaDesaguaderoOeste = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "balanza_ascendente", "type" => "booleano", "label" => "Balanza Ascendente (Operativa)"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ];

        $schemaDesaguaderoEste = [
            "inventory_fields" => [
                ["name" => "estado_barreras", "type" => "booleano", "label" => "Estado de Barreras"],
                ["name" => "energia_red", "type" => "booleano", "label" => "Energía de Red Activa"],
                ["name" => "balanza_descendente", "type" => "booleano", "label" => "Balanza Descendente (Operativa)"],
                ["name" => "observaciones", "type" => "texto", "label" => "Observaciones Generales"]
            ]
        ];

         
        $this->command->info('Instalando Estaciones de Peaje con sus fotografías oficiales...');
        
        $peajesData = [
            ['name' => 'Peaje La Cumbre',         'schema' => $schemaLaCumbre,         'filename' => 'la-cumbre.jpeg'],
            ['name' => 'Peaje Desaguadero Este',  'schema' => $schemaDesaguaderoEste,  'filename' => 'desaguadero-e.jpeg'],
            ['name' => 'Peaje Desaguadero Oeste', 'schema' => $schemaDesaguaderoOeste, 'filename' => 'desaguadero-o.jpeg'],
            ['name' => 'Peaje Los Puquios',       'schema' => $schemaGenerico,         'filename' => 'los-puquios.jpeg'],
            ['name' => 'Peaje Cruz de Piedra',    'schema' => $schemaGenerico,         'filename' => 'cruz-piedra.jpeg'],
            ['name' => 'Peaje Perilago',          'schema' => $schemaGenerico,         'filename' => 'perilago.jpeg'],
            ['name' => 'Peaje Ruta 30',           'schema' => $schemaGenerico,         'filename' => 'ruta-30.jpeg'],
        ];

        foreach ($peajesData as $peaje) {
            $imagePath = null;
           
            $origenImagen = public_path('img/peajes/' . $peaje['filename']);
            
            if (file_exists($origenImagen)) {
                $destino = 'peajes/' . $peaje['filename'];
                
            
                Storage::disk('public')->put($destino, file_get_contents($origenImagen));
                
         
                $imagePath = '/storage/' . $destino;
            } else {
             
                $this->command->warn("Falta la foto: {$peaje['filename']}. El {$peaje['name']} quedará sin imagen.");
            }

            Toll::create([
                'name' => $peaje['name'],
                'dynamic_schema' => $peaje['schema'],
                'dynamic_data' => [],  
                'image_path' => $imagePath 
            ]);
        }

        $this->command->info('Instalación completada exitosamente');
    }
}