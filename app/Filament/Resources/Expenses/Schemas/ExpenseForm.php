<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pengeluaran')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->required(),

                        Select::make('expense_category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->columnSpanFull()
                            ->prefix('Rp')
                            ->minValue(1)
                            ->required(),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        FileUpload::make('attachment')
                            ->label('Bukti (foto)')
                            ->image()
                            ->disk('public')
                            ->directory('expenses')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Foto nota atau kwitansi, maksimal 2 MB.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}