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
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('ram')->nullable()->index();        // GB
            $table->unsignedSmallInteger('storage')->nullable()->index();    // GB
            $table->decimal('screen_size', 3, 1)->nullable();                // inci
            $table->unsignedSmallInteger('battery')->nullable();             // mAh
            $table->unsignedSmallInteger('camera_mp')->nullable();           // MP kamera utama
            $table->string('chipset', 100)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('network', 10)->nullable()->index();              // 4G | 5G
            $table->string('color', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specs');
    }
};
