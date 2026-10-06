<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasExportActions;
use App\Filament\Support\ReportFilterFields;
use App\Filament\Widgets\ExpenseBreakdownTable;
use App\Filament\Widgets\ProfitLossStats;
use App\Filament\Widgets\ProfitTrendChart;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProfitLossReport extends BaseDashboard
{
    use HasFiltersForm;
    use HasExportActions;

    protected static string $routePath = 'laba-rugi';

    public static function getNavigationLabel(): string
    {
        return 'Laba Rugi';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public static function getNavigationSort(): ?int
    {
        return 30;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedScale;
    }

    public function getTitle(): string
    {
        return 'Laporan Laba Rugi';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    protected function exportKey(): string
    {
        return 'profit-loss';
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            ProfitLossStats::class,
            ProfitTrendChart::class,
            ExpenseBreakdownTable::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(3)
                ->columnSpanFull()
                ->schema(ReportFilterFields::dateRange()),
        ]);
    }
}