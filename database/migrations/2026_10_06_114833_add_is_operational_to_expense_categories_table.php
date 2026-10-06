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
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->boolean('is_operational')->default(true)->after('name');
        });

        // Pembelian stok sudah dihitung sebagai HPP saat barang terjual.
        DB::table('expense_categories')
            ->where('name', 'Pembelian Stok Lainnya')
            ->update(['is_operational' => false]);
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('is_operational');
        });
    }
};
