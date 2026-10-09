<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('track_imei')->default(false)->after('is_active');
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('imei', 30)->nullable()->unique();
            $table->string('imei2', 30)->nullable()->unique();
            $table->string('status', 15)->default('tersedia')->index(); // tersedia | terjual
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('imei2', 30)->nullable()->after('imei');
            $table->foreignId('unit_id')->nullable()->after('imei2')
                ->constrained('product_units')->nullOnDelete();
        });

        // Produk handphone yang sudah ada langsung ditawari pencatatan IMEI.
        DB::table('products')
            ->whereIn('category_id', DB::table('categories')->where('type', 'handphone')->select('id'))
            ->update(['track_imei' => true]);
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('imei2');
        });

        Schema::dropIfExists('product_units');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('track_imei');
        });
    }
};
