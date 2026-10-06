<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReadsReportFilters;
use App\Models\ExpenseCategory;
use App\Support\DateRange;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ExpenseBreakdownTable extends TableWidget
{
    use ReadsReportFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pengeluaran Operasional per Kategori')
            ->query(function () {
                [$from, $until] = DateRange::resolve($this->reportFilters());
                $range = [$from->toDateString(), $until->toDateString()];

                return ExpenseCategory::query()
                    ->where('is_operational', true)
                    ->whereHas('expenses', fn ($q) => $q->whereBetween('date', $range))
                    ->withSum(['expenses as total' => fn ($q) => $q->whereBetween('date', $range)], 'amount');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori'),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => rupiah($state))
                    ->sortable(),
            ])
            ->defaultSort('total', 'desc')
            ->paginated(false);
    }
}