<?php

namespace App\Filament\Widgets;

use App\Models\SaleItem;
use App\Support\ReportQuery;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProductsTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Produk Terlaris Bulan Ini')
            ->query(fn () => SaleItem::query()
                ->whereIn('sale_id', ReportQuery::sales(['preset' => 'month'])->select('sales.id'))
                ->selectRaw('MIN(id) as id, product_id, MAX(product_name) as product_name, SUM(qty) as qty, SUM(subtotal) as revenue')
                ->groupBy('product_id')
                ->limit(5))
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produk'),

                TextColumn::make('qty')
                    ->label('Terjual'),

                TextColumn::make('revenue')
                    ->label('Omset')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),
            ])
            ->defaultSort('qty', 'desc')
            ->paginated(false)
            ->emptyStateHeading('Belum ada penjualan bulan ini');
    }
}