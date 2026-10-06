<?php

namespace App\Filament\Widgets;

use App\Support\ReportQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        // ReportQuery otomatis membatasi data kasir ke transaksinya sendiri.
        $row = ReportQuery::saleItems(['preset' => 'today'])
            ->toBase()
            ->selectRaw('
                COUNT(DISTINCT sale_id) as trx,
                COALESCE(SUM(qty), 0) as qty,
                COALESCE(SUM(subtotal), 0) as revenue
            ')
            ->first();

        $trx = (int) $row->trx;
        $revenue = (int) $row->revenue;

        return [
            Stat::make('Penjualan Hari Ini', rupiah($revenue)),
            Stat::make('Transaksi Hari Ini', number_format($trx, 0, ',', '.')),
            Stat::make('Item Terjual', number_format((int) $row->qty, 0, ',', '.')),
            Stat::make('Rata-rata / Transaksi', rupiah($trx > 0 ? intdiv($revenue, $trx) : 0)),
        ];
    }
}