<?php

namespace Database\Seeders;

use App\Models\ReceiptSetting;
use Illuminate\Database\Seeder;

class ReceiptSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (ReceiptSetting::query()->exists()) {
            return;
        }

        $size = (string) config('store.receipt_size', '58');

        ReceiptSetting::create([
            'name' => 'Pengaturan Utama',
            'is_active' => true,
            'store_name' => config('store.name', config('app.name')),
            'store_address' => config('store.address') ?: null,
            'store_phone' => config('store.phone') ?: null,
            'footer' => config('store.footer'),
            'paper_type' => in_array($size, ['58', '80', 'a5'], true) ? $size : '58',
            'show_logo' => true,
            'auto_print' => false,
        ]);
    }
}