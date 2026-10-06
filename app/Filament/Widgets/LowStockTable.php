<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stok Menipis')
            ->query(fn () => Product::query()
                ->where('is_active', true)
                ->whereColumn('stock', '<=', 'min_stock')
                ->limit(10))
            ->columns([
                TextColumn::make('name')
                    ->label('Produk'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->color(fn (Product $record): string => $record->stock <= 0 ? 'danger' : 'warning'),

                TextColumn::make('min_stock')
                    ->label('Minimum'),
            ])
            ->defaultSort('stock', 'asc')
            ->paginated(false)
            ->emptyStateHeading('Semua stok aman');
    }
}