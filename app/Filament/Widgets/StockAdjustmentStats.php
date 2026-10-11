<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\ReportQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAdjustmentStats extends StatsOverviewWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $row = ReportQuery::stockMovements($this->reportFilters())
            ->toBase()
            ->selectRaw('
                COUNT(*) as total,
                COUNT(DISTINCT product_id) as products,
                COALESCE(SUM(CASE WHEN qty > 0 THEN qty ELSE 0 END), 0) as plus,
                COALESCE(SUM(CASE WHEN qty < 0 THEN -qty ELSE 0 END), 0) as minus
            ')
            ->first();

        return [
            Stat::make('Jumlah Pergerakan', number_format((int) $row->total, 0, ',', '.')),
            Stat::make('Produk Terdampak', number_format((int) $row->products, 0, ',', '.')),
            Stat::make('Total Qty Bertambah', number_format((int) $row->plus, 0, ',', '.'))
                ->color('success'),
            Stat::make('Total Qty Berkurang', number_format((int) $row->minus, 0, ',', '.'))
                ->color($row->minus > 0 ? 'danger' : 'gray'),
        ];
    }
}