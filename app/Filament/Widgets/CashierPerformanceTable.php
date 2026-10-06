<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;

class CashierPerformanceTable extends CashierOmsetTable
{
    public function table(Table $table): Table
    {
        return parent::table($table)
            ->heading('Performa Kasir Bulan Ini');
    }
}