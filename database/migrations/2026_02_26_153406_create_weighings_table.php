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
    Schema::create('weighings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('toll_id')->constrained('tolls');
        $table->string('license_plate');
        $table->string('driver_dni')->nullable();
        $table->decimal('weight_kg', 10, 2); // Hasta 99 millones de kg con 2 decimales
        $table->timestamps();
        $table->softDeletes();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weighings');
    }
};
