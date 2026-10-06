<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Toggle;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        Toggle::make('is_operational')
                            ->label('Dihitung sebagai pengeluaran operasional')
                            ->helperText('Matikan untuk pembelian barang dagangan (modalnya sudah masuk HPP), supaya tidak terhitung dua kali di laba rugi.')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}