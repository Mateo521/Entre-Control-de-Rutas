<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tow_services', function (Blueprint $table) {
            $table->id();
       
            $table->enum('tow_truck', ['Grúa Provincial', 'Grúa Desaguadero', 'Grúa La Cumbre']);
            $table->string('license_plate'); 
            $table->string('location');      
            $table->string('reason');       
            $table->text('observations')->nullable();  
            $table->timestamps();
            $table->softDeletes();           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tow_services');
    }
};