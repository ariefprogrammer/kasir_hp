<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CashierPerformanceTable;
use App\Filament\Widgets\Last14DaysChart;
use App\Filament\Widgets\LowStockTable;
use App\Filament\Widgets\OwnerStats;
use App\Filament\Widgets\QuickLinks;
use App\Filament\Widgets\TodayStats;
use App\Filament\Widgets\TodayTransactionsTable;
use App\Filament\Widgets\TopProductsTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getTitle(): string
    {
        return auth()->user()?->isManager()
            ? 'Dashboard'
            : 'Halo, ' . auth()->user()?->name;
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        if (auth()->user()?->isManager()) {
            return [
                OwnerStats::class,
                Last14DaysChart::class,
                TopProductsTable::class,
                LowStockTable::class,
                CashierPerformanceTable::class,
            ];
        }

        return [
            QuickLinks::class,
            TodayStats::class,
            TodayTransactionsTable::class,
        ];
    }
}