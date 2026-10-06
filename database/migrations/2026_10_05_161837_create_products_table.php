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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name')->index();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('brand', 100)->nullable()->index();
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('sell_price')->default(0);
            $table->integer('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->string('condition', 10)->default('baru'); // baru | second
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
