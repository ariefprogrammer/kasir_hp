<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('products_count')
                    ->label('Jumlah produk')
                    ->counts('products'),
            ])
            ->recordActions([
                EditAction::make(),

                DeleteAction::make()
                    ->hidden(fn (Category $record): bool => $record->products()->withTrashed()->exists()),
            ])
            ->defaultSort('name');
    }
}