<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportFilterFields;
use App\Filament\Widgets\DailySalesChart;
use App\Filament\Widgets\SalesItemsTable;
use App\Filament\Widgets\SalesStats;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use App\Filament\Pages\Concerns\HasExportActions;

class SalesReport extends BaseDashboard
{
    use HasFiltersForm, HasExportActions;

    protected static string $routePath = 'laporan-penjualan';

    public static function getNavigationLabel(): string
    {
        return 'Laporan Penjualan';
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
        return Heroicon::OutlinedChartBar;
    }

    public function getTitle(): string
    {
        return 'Laporan Penjualan';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isManager() ?? false;
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getWidgets(): array
    {
        return [
            SalesStats::class,
            DailySalesChart::class,
            SalesItemsTable::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(5)
                ->columnSpanFull()
                ->schema([
                    ...ReportFilterFields::dateRange(),

                    Select::make('cashier_id')
                        ->label('Kasir')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua kasir'),

                    Select::make('payment_method')
                        ->label('Metode bayar')
                        ->options(['tunai' => 'Tunai', 'transfer' => 'Transfer', 'qris' => 'QRIS'])
                        ->placeholder('Semua metode'),

                    Select::make('category_id')
                        ->label('Kategori')
                        ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                        ->placeholder('Semua kategori'),

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

    protected function exportKey(): string
    {
        return 'sales';
    }
}