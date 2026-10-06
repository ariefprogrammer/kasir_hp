<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),

                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'handphone' => 'Handphone',
                                'aksesoris' => 'Aksesoris',
                            ])
                            ->default('aksesoris')
                            ->required()
                            ->helperText('Tipe Handphone menampilkan isian spesifikasi (RAM, storage, dst.) di form produk.'),
                    ]),
            ]);
    }
}