<?php

namespace App\Filament\Resources\ReceiptSettings\Schemas;

use App\Models\ReceiptSetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ReceiptSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil Pengaturan')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama pengaturan')
                            ->placeholder('Ex : Printer kasir depan')
                            ->required()
                            ->maxLength(100),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(fn (): bool => ! ReceiptSetting::query()->exists())
                            ->disabled(fn (?ReceiptSetting $record): bool => (bool) $record?->is_active)
                            ->helperText('Hanya satu pengaturan yang bisa aktif')
                            ->inline(false),
                    ]),

                Section::make('Detail Toko')
                    ->columns(2)
                    ->schema([
                        TextInput::make('store_name')
                            ->label('Nama toko')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('store_phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(30),

                        Textarea::make('store_address')
                            ->label('Alamat')
                            ->rows(2)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('footer')
                            ->label('Footer struk')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Contoh: syarat garansi atau ucapan terima kasih.')
                            ->columnSpanFull(),

                        FileUpload::make('logo')
                            ->label('Logo toko')
                            ->image()
                            ->columnSpanFull()
                            ->disk('public')
                            ->directory('store')
                            ->visibility('public')
                            ->maxSize(1024)
                            ->helperText('Maksimal 1 MB. Untuk printer thermal, pakai logo hitam putih dengan kontras tinggi.'),

                        Toggle::make('show_logo')
                            ->columnSpanFull()
                            ->label('Tampilkan logo di struk')
                            ->default(true)
                            ->inline(false),
                    ]),

                Section::make('Ukuran Kertas & Cetak')
                    ->columns(2)
                    ->schema([
                        Select::make('paper_type')
                            ->label('Ukuran kertas')
                            ->options([
                                '58' => 'Thermal 58 mm',
                                '80' => 'Thermal 80 mm',
                                'a5' => 'A5',
                                'custom' => 'Kustom',
                            ])
                            ->default('58')
                            ->required()
                            ->live(),

                        TextInput::make('custom_width_mm')
                            ->label('Lebar kustom')
                            ->numeric()
                            ->suffix('mm')
                            ->minValue(40)
                            ->maxValue(210)
                            ->required(fn (Get $get): bool => $get('paper_type') === 'custom')
                            ->visible(fn (Get $get): bool => $get('paper_type') === 'custom'),

                        TextInput::make('font_size')
                            ->label('Ukuran font')
                            ->numeric()
                            ->suffix('px')
                            ->minValue(8)
                            ->maxValue(20)
                            ->placeholder('Otomatis')
                            ->helperText('Kosongkan untuk mengikuti ukuran kertas.'),

                        Toggle::make('auto_print')
                            ->label('Cetak otomatis saat struk dibuka')
                            ->inline(false),
                    ]),
            ]);
    }
}