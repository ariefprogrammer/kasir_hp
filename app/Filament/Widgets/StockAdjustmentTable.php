<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Models\StockMovement;
use App\Support\ReportQuery;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class StockAdjustmentTable extends TableWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Riwayat Pergerakan Stok')
            ->query(fn () => ReportQuery::stockMovements($this->reportFilters())
                ->with(['product' => fn ($q) => $q->withTrashed(), 'user:id,name']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('product.sku')
                    ->label('SKU'),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => StockMovement::typeLabel((string) $state))
                    ->color(fn ($state): string => StockMovement::TYPE_COLORS[$state] ?? 'gray'),

                TextColumn::make('qty')
                    ->label('Jumlah')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => ((int) $state > 0 ? '+' : '') . number_format((int) $state, 0, ',', '.'))
                    ->color(fn ($state): string => (int) $state < 0 ? 'danger' : 'success'),

                TextColumn::make('reference')
                    ->label('Referensi')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Oleh')
                    ->placeholder('-'),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->placeholder('-')
                    ->wrap()
                    ->searchable(),
            ])
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50]);
    }
}