<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
    {
        Schema::create('actions', function (Blueprint $table) {
            $table->id();
            // toll_id es nullable porque hay acciones que son de toda la "Región Centro" y no de un peaje en particular
            $table->foreignId('toll_id')->nullable()->constrained('tolls')->nullOnDelete();
            
            $table->string('title'); // Ej: "Creación de cuadrilla de bacheo"
            $table->text('description'); // El texto largo del informe
            $table->json('media_paths')->nullable(); // Portafolio de evidencia (imágenes del trabajo)
            
            $table->timestamps();
            $table->softDeletes(); // Borrado lógico (Archivado)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
