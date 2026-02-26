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
    Schema::create('tolls', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        // El tipo jsonb es exclusivo y optimizado para PostgreSQL
        $table->jsonb('dynamic_schema')->nullable(); 
        $table->timestamps();
        $table->softDeletes(); // Habilita el archivo histórico
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tolls');
    }
};
