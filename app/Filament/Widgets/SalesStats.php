<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\ReportQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $row = ReportQuery::saleItems($this->reportFilters())
            ->toBase()
            ->selectRaw('
                COUNT(DISTINCT sale_id) as trx,
                COALESCE(SUM(qty), 0) as qty,
                COALESCE(SUM(subtotal), 0) as revenue,
                COALESCE(SUM((standard_price - deal_price) * qty), 0) as diff
            ')
            ->first();

        return [
            Stat::make('Jumlah Transaksi', number_format((int) $row->trx, 0, ',', '.')),
            Stat::make('Item Terjual', number_format((int) $row->qty, 0, ',', '.')),
            Stat::make('Total Penjualan', rupiah($row->revenue)),
            Stat::make('Total Selisih Nego', rupiah($row->diff))
                ->description('Positif = diskon dari harga standar'),
        ];
    }
}