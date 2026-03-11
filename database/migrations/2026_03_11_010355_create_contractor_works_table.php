<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('contractor_works', function (Blueprint $table) {
        $table->id();
        $table->string('company_name');  
        $table->enum('work_type', ['Corte de pasto', 'Poda correctiva', 'Pintura', 'Mantenimiento general']);  
        $table->string('location');  
        $table->text('description')->nullable();  
        $table->enum('status', ['En progreso', 'Finalizado', 'Certificado para pago'])->default('En progreso');
        $table->json('media_paths')->nullable();  
        
        $table->softDeletes();  
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contractor_works');
    }
};
