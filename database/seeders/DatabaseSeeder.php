<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
      
        $this->call(SystemDataSeeder::class);
        
     
        $this->call(InventorySeeder::class);
        
    
        $this->call(BacheoSeeder::class);
        
      
        $this->call(ContractorWorkSeeder::class);
    }
}