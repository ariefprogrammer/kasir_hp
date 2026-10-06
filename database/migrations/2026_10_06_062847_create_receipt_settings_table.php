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
        Schema::create('receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(false)->index();

            $table->string('store_name', 100);
            $table->string('store_address')->nullable();
            $table->string('store_phone', 30)->nullable();
            $table->text('footer')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('show_logo')->default(true);

            $table->string('paper_type', 10)->default('58'); // 58 | 80 | a5 | custom
            $table->unsignedSmallInteger('custom_width_mm')->nullable();
            $table->unsignedTinyInteger('font_size')->nullable(); // null = otomatis
            $table->boolean('auto_print')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_settings');
    }
};
