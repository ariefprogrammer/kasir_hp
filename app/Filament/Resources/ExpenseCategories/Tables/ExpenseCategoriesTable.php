<?php

namespace App\Filament\Resources\ExpenseCategories\Tables;

use App\Models\ExpenseCategory;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;

class ExpenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('expenses_count')
                    ->label('Jumlah pengeluaran')
                    ->counts('expenses'),

                IconColumn::make('is_operational')
                    ->label('Operasional')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),

                // Kategori yang sudah dipakai tidak boleh dihapus (foreign key restrict).
                DeleteAction::make()
                    ->hidden(fn (ExpenseCategory $record): bool => $record->expenses()->withTrashed()->exists()),
            ])
            ->defaultSort('name');
    }
}