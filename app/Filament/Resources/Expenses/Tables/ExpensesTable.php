<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Models\Expense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge(),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state): string => rupiah($state))
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn ($state): string => rupiah($state))
                    ),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn (Expense $record): ?string => $record->description),

                TextColumn::make('user.name')
                    ->label('Dicatat oleh'),

                ImageColumn::make('attachment')
                    ->label('Bukti')
                    ->disk('public')
                    ->square()
                    ->url(fn (Expense $record): ?string => $record->attachment
                        ? asset('storage/' . $record->attachment)
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Filter::make('date')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('date', '<=', $date))),

                SelectFilter::make('expense_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}