<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            // Agregamos la columna después del toll_id. 
            // Le damos un valor por defecto por si hiciste alguna prueba previa y no rompa la BD.
            $table->string('category')->after('toll_id')->default('Mantenimiento Vial');
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
