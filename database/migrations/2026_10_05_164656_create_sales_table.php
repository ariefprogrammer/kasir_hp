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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 30)->unique();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('customer_name', 100)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->unsignedBigInteger('subtotal')->default(0); // total harga standar
            $table->unsignedBigInteger('total')->default(0);    // total harga deal
            $table->string('payment_method', 20)->default('tunai');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->string('status', 10)->default('selesai')->index(); // selesai | void
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->dateTime('sold_at')->index();
            $table->timestamps();

            $table->index(['cashier_id', 'sold_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
