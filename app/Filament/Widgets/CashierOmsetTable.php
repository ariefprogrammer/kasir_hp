<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Models\User;
use App\Support\ReportQuery;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CashierOmsetTable extends TableWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Omset per Kasir')
            ->query(fn () => ReportQuery::cashierTotals($this->reportFilters()))
            ->columns([
                TextColumn::make('name')
                    ->label('Kasir'),

                TextColumn::make('trx_count')
                    ->label('Transaksi')
                    ->sortable(),

                TextColumn::make('items_qty')
                    ->label('Item terjual')
                    ->sortable(),

                TextColumn::make('revenue')
                    ->label('Total omset')
                    ->formatStateUsing(fn ($state): string => rupiah($state))
                    ->sortable(),

                TextColumn::make('average')
                    ->label('Rata-rata / transaksi')
                    ->state(fn (User $record): int => $record->trx_count > 0
                        ? intdiv((int) $record->revenue, (int) $record->trx_count)
                        : 0)
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('diff')
                    ->label('Selisih nego')
                    ->state(fn (User $record): int => (int) $record->list_total - (int) $record->revenue)
                    ->formatStateUsing(fn ($state): string => rupiah($state)),
            ])
            ->defaultSort('revenue', 'desc')
            ->paginated(false);
    }
}