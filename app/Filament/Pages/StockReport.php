<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasExportActions;
use App\Filament\Widgets\StockStats;
use App\Filament\Widgets\StockTable;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class StockReport extends BaseDashboard
{
    use HasExportActions;

    protected static string $routePath = 'laporan-stok';

    public static function getNavigationLabel(): string
    {
        return 'Laporan Stok';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public static function getNavigationSort(): ?int
    {
        return 10;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedArchiveBox;
    }

    public function getTitle(): string
    {
        return 'Laporan Stok';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    protected function exportKey(): string
    {
        return 'stock';
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            StockStats::class,
            StockTable::class,
        ];
    }
}