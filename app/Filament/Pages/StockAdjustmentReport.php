<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\HasExportActions;
use App\Filament\Support\ReportFilterFields;
use App\Filament\Widgets\StockAdjustmentStats;
use App\Filament\Widgets\StockAdjustmentTable;
use App\Models\Product;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class StockAdjustmentReport extends BaseDashboard
{
    use HasFiltersForm, HasExportActions;

    protected static string $routePath = 'laporan-penyesuaian-stok';

    public static function getNavigationLabel(): string
    {
        return 'Laporan Penyesuaian Stok';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    public static function getNavigationSort(): ?int
    {
        return 11;
    }

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedArrowsUpDown;
    }

    public function getTitle(): string
    {
        return 'Laporan Penyesuaian Stok';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    protected function exportKey(): string
    {
        return 'stock-adjustments';
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            StockAdjustmentStats::class,
            StockAdjustmentTable::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    ...ReportFilterFields::dateRange(),

                    Select::make('movement_types')
                        ->label('Jenis pergerakan')
                        ->multiple()
                        ->options(fn (): array => StockMovement::typeOptions())
                        ->default(['in', 'adjustment'])
                        ->placeholder('Semua jenis'),

                    Select::make('product_id')
                        ->label('Produk')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search) => Product::withTrashed()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(20)
                            ->pluck('name', 'id'))
                        ->getOptionLabelUsing(fn ($value) => Product::withTrashed()->find($value)?->name)
                        ->placeholder('Semua produk'),
                ]),
        ]);
    }
}