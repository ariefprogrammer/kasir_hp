<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Support\ProfitLoss;
use App\Support\ReportQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OwnerStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    // Memuat laba dan modal, jadi hanya untuk Owner/Admin.
    public static function canView(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    protected function getStats(): array
    {
        $today = ReportQuery::sales(['preset' => 'today']);
        $todayTotal = (int) (clone $today)->sum('total');
        $todayCount = (clone $today)->count();

        $month = ProfitLoss::calculate(['preset' => 'month']);

        $lowStock = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        return [
            Stat::make('Omset Hari Ini', rupiah($todayTotal))
                ->description($todayCount . ' transaksi'),

            Stat::make('Omset Bulan Ini', rupiah($month['revenue'])),

            Stat::make('Laba Kotor Bulan Ini', rupiah($month['gross']))
                ->description('Margin ' . number_format($month['gross_margin'], 1, ',', '.') . '%'),

            Stat::make('Pengeluaran Bulan Ini', rupiah($month['opex']))
                ->description('Operasional'),

            Stat::make('Laba Bersih Bulan Ini', rupiah($month['net']))
                ->description('Margin ' . number_format($month['net_margin'], 1, ',', '.') . '%')
                ->color($month['net'] >= 0 ? 'success' : 'danger'),

            Stat::make('Stok Menipis', $lowStock . ' produk')
                ->description('Stok sama dengan atau di bawah minimum')
                ->color($lowStock > 0 ? 'warning' : 'success'),
        ];
    }
}