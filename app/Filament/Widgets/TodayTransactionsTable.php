<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TodayTransactionsTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transaksi Hari Ini (klik untuk membuka struk)')
            ->query(fn () => Sale::query()
                ->completed()
                ->visibleTo(auth()->user())
                ->whereDate('sold_at', today()))
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('No. Invoice'),

                TextColumn::make('sold_at')
                    ->label('Jam')
                    ->dateTime('H:i'),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('payment_method')
                    ->label('Bayar')
                    ->badge(),
            ])
            ->recordUrl(fn (Sale $record): string => route('receipt.show', $record), shouldOpenInNewTab: true)
            ->defaultSort('sold_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->emptyStateHeading('Belum ada transaksi hari ini');
    }
}