<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Support\ProfitLoss;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitLossStats extends StatsOverviewWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected function getStats(): array
    {
        $r = ProfitLoss::calculate($this->reportFilters());

        $margin = fn ($value) => 'Margin ' . number_format($value, 1, ',', '.') . '%';

        return [
            Stat::make('Pendapatan', rupiah($r['revenue']))
                ->description('Harga deal x qty'),

            Stat::make('HPP', rupiah($r['cogs']))
                ->description('Modal barang terjual'),

            Stat::make('Laba Kotor', rupiah($r['gross']))
                ->description($margin($r['gross_margin'])),

            Stat::make('Pengeluaran Operasional', rupiah($r['opex']))
                ->description($r['non_operational'] > 0
                    ? 'Tidak termasuk pembelian stok ' . rupiah($r['non_operational'])
                    : null),

            Stat::make('Laba Bersih', rupiah($r['net']))
                ->description($margin($r['net_margin']))
                ->color($r['net'] >= 0 ? 'success' : 'danger'),
        ];
    }
}