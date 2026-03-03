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
    Schema::create('inventory_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained();  
        $table->enum('type', ['in', 'out', 'adjustment']);  
        $table->decimal('quantity', 10, 2);
        $table->string('reference_document')->nullable();  
        $table->text('description')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
