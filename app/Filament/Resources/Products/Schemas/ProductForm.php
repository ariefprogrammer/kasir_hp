<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make('Informasi Produk')
                            ->columns(2)
                            ->extraAttributes(['style' => 'height: 100%'])
                            ->schema([
                                Select::make('category_id')
                                    ->label('Kategori')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, string $operation): void {
                                        if ($operation === 'create' && $state) {
                                            $set('track_imei', Category::whereKey($state)->value('type') === 'handphone');
                                        }
                                    })
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nama kategori')
                                            ->required(),
                                        Select::make('type')
                                            ->label('Tipe')
                                            ->options([
                                                'handphone' => 'Handphone',
                                                'aksesoris' => 'Aksesoris',
                                            ])
                                            ->default('aksesoris')
                                            ->required(),
                                    ]),

                                TextInput::make('name')
                                    ->label('Nama produk')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),

                                TextInput::make('barcode')
                                    ->label('Barcode')
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(50),

                                TextInput::make('brand')
                                    ->label('Merek')
                                    ->maxLength(100),

                                Select::make('condition')
                                    ->label('Kondisi')
                                    ->options([
                                        'baru' => 'Baru',
                                        'second' => 'Second',
                                    ])
                                    ->default('baru')
                                    ->required(),

                                Toggle::make('track_imei')
                                    ->label('Catat IMEI / serial per unit')
                                    ->helperText('Opsional. Untuk handphone, tablet, dan barang berserial lain. Isi IMEI-nya di tab "Unit IMEI" setelah produk disimpan.')
                                    ->default(false)
                                    ->inline(false),
                                    
                                FileUpload::make('photo')
                                    ->label('Foto')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Harga & Stok')
                            ->columns(2)
                            ->extraAttributes(['class' => 'h-[500px]'])
                            ->schema([
                                TextInput::make('cost_price')
                                    ->label('Harga modal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->visible(fn (): bool => auth()->user()->canViewCost()),

                                TextInput::make('sell_price')
                                    ->label('Harga jual standar')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->required(),

                                TextInput::make('min_stock')
                                    ->label('Stok minimum')
                                    ->numeric()
                                    ->columnSpanFull()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required(),

                                TextInput::make('stock')
                                    ->label('Stok awal')
                                    ->numeric()
                                    ->columnSpanFull()
                                    ->minValue(0)
                                    ->default(0)
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                    ->helperText('Setelah produk dibuat, ubah stok lewat aksi "Sesuaikan stok" di tabel.'),

                                Toggle::make('is_active')
                                    ->label('Produk aktif')
                                    ->default(true)
                                    ->columnSpanFull()
                                    ->extraAttributes(['class' => 'mb-6'])
                                    ->inline(false),
                            ]),
            
                ]),
                Group::make()
                    ->columnSpanFull()
                    ->visible(function (Get $get): bool {
                        $categoryId = $get('category_id');

                        return $categoryId
                            && Category::whereKey($categoryId)->value('type') === 'handphone';
                    })
                    ->schema([
                        Section::make('Spesifikasi')
                            ->relationship('spec')
                            ->columns(3)
                            ->schema([
                                TextInput::make('ram')
                                    ->label('RAM')
                                    ->numeric()
                                    ->suffix('GB'),

                                TextInput::make('storage')
                                    ->label('Storage internal')
                                    ->numeric()
                                    ->suffix('GB'),

                                TextInput::make('screen_size')
                                    ->label('Ukuran layar')
                                    ->numeric()
                                    ->suffix('inci'),

                                TextInput::make('battery')
                                    ->label('Baterai')
                                    ->numeric()
                                    ->suffix('mAh'),

                                TextInput::make('camera_mp')
                                    ->label('Kamera utama')
                                    ->numeric()
                                    ->suffix('MP'),

                                TextInput::make('chipset')
                                    ->label('Chipset')
                                    ->maxLength(100),

                                TextInput::make('os')
                                    ->label('Sistem operasi')
                                    ->maxLength(50),

                                Select::make('network')
                                    ->label('Jaringan')
                                    ->options([
                                        '4G' => '4G',
                                        '5G' => '5G',
                                    ]),

                                TextInput::make('color')
                                    ->label('Warna')
                                    ->maxLength(50),
                            ]),
                    ]),
            ]);
    }
}