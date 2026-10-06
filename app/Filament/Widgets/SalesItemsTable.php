<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Models\SaleItem;
use App\Support\ReportQuery;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class SalesItemsTable extends TableWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Rincian Penjualan')
            ->query(fn () => ReportQuery::saleItems($this->reportFilters())->with('sale.cashier:id,name'))
            ->columns([
                TextColumn::make('sale.invoice_no')
                    ->label('No. Invoice')
                    ->searchable(),

                TextColumn::make('sale.sold_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i'),

                TextColumn::make('sale.cashier.name')
                    ->label('Kasir'),

                TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('qty')
                    ->label('Qty'),

                TextColumn::make('standard_price')
                    ->label('Harga standar')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('deal_price')
                    ->label('Harga deal')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('diff')
                    ->label('Selisih nego')
                    ->state(fn (SaleItem $record): int => ($record->standard_price - $record->deal_price) * $record->qty)
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state): string => rupiah($state)),

                TextColumn::make('sale.payment_method')
                    ->label('Bayar')
                    ->badge(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}