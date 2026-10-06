<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportFilterFields;
use App\Filament\Widgets\CashierComparisonChart;
use App\Filament\Widgets\CashierOmsetTable;
use App\Filament\Widgets\DailySalesChart;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Filament\Pages\Concerns\HasExportActions;

class CashierOmsetReport extends BaseDashboard
{
    use HasFiltersForm, HasExportActions;

    protected static string $routePath = 'omset-kasir';

    public static function getNavigationLabel(): string
    {
        return 'Omset per Kasir';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public static function getNavigationSort(): ?int
    {
        return 9;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedUserGroup;
    }

    public function getTitle(): string
    {
        return 'Laporan Omset per Kasir';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_active;
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            CashierOmsetTable::class,
            CashierComparisonChart::class,
            DailySalesChart::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    ...ReportFilterFields::dateRange(),

                    Select::make('cashier_id')
                        ->label('Kasir')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua kasir')
                        ->visible(fn (): bool => auth()->user()->isManager()),
                ]),
        ]);
    }

    protected function exportKey(): string
    {
        return 'cashiers';
    }
}