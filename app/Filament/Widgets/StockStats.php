<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $row = Product::query()
            ->where('is_active', true)
            ->toBase()
            ->selectRaw('
                COUNT(*) as products,
                COALESCE(SUM(stock), 0) as units,
                COALESCE(SUM(stock * cost_price), 0) as value,
                COALESCE(SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END), 0) as low
            ')
            ->first();

        return [
            Stat::make('Produk Aktif', number_format((int) $row->products, 0, ',', '.')),
            Stat::make('Total Unit Stok', number_format((int) $row->units, 0, ',', '.')),
            Stat::make('Nilai Persediaan', rupiah($row->value))
                ->description('Stok x harga modal'),
            Stat::make('Stok Menipis', number_format((int) $row->low, 0, ',', '.'))
                ->description('Stok sama dengan atau di bawah minimum')
                ->color($row->low > 0 ? 'warning' : 'success'),
        ];
    }
}