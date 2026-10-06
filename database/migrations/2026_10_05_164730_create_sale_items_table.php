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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('product_name');
            $table->string('imei', 20)->nullable(); // opsional, IMEI tidak wajib
            $table->unsignedInteger('qty');
            $table->unsignedBigInteger('cost_price');     // snapshot modal
            $table->unsignedBigInteger('standard_price'); // snapshot harga standar
            $table->unsignedBigInteger('deal_price');     // harga hasil nego
            $table->unsignedBigInteger('subtotal');       // deal_price x qty
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
