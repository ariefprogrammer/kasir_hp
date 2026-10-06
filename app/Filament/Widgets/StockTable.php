<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StockTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Daftar Stok Produk Aktif')
            ->query(fn () => Product::query()->where('is_active', true)->with('category:id,name'))
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->sortable()
                    ->color(fn (Product $record): string => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('min_stock')
                    ->label('Stok min'),

                TextColumn::make('cost_price')
                    ->label('Harga modal')
                    ->formatStateUsing(fn ($state): string => rupiah($state))
                    ->sortable(),

                TextColumn::make('inventory_value')
                    ->label('Nilai persediaan')
                    ->state(fn (Product $record): int => $record->stock * $record->cost_price)
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('sell_price')
                    ->label('Harga jual')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                Filter::make('low_stock')
                    ->label('Hanya stok menipis')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock')),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }
}