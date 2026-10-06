<?php

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;

class ReportFilterFields
{
    public static function dateRange(): array
    {
        return [
            Select::make('preset')
                ->label('Periode')
                ->options([
                    'today' => 'Hari ini',
                    'week' => 'Minggu ini',
                    'month' => 'Bulan ini',
                    'custom' => 'Kustom',
                ])
                ->default('month')
                ->selectablePlaceholder(false)
                ->live(),

            DatePicker::make('startDate')
                ->label('Dari tanggal')
                ->visible(fn (Get $get): bool => $get('preset') === 'custom'),

            DatePicker::make('endDate')
                ->label('Sampai tanggal')
                ->visible(fn (Get $get): bool => $get('preset') === 'custom'),
        ];
    }
}