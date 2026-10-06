<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Gaji', 'Listrik', 'Sewa', 'Internet', 'Operasional', 'Pembelian Stok Lainnya'] as $name) {
            \App\Models\ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
