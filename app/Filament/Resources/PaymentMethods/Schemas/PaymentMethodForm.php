<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use App\Models\PaymentMethod;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentMethodForm
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
                            ->label('Nama tampilan')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->regex('/^[a-z0-9_]+$/')
                            ->maxLength(30)
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Huruf kecil, angka, dan garis bawah. Tidak bisa diubah setelah dibuat karena tersimpan di transaksi.'),

                        Toggle::make('is_cash')
                            ->label('Metode tunai (ada uang diterima dan kembalian)')
                            ->default(false)
                            ->inline(false),

                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->inline(false)
                            ->rules([
                                fn (?PaymentMethod $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                    if ($value) {
                                        return;
                                    }

                                    $otherActive = PaymentMethod::query()
                                        ->where('is_active', true)
                                        ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                        ->exists();

                                    if (! $otherActive) {
                                        $fail('Minimal harus ada satu metode pembayaran yang aktif.');
                                    }
                                },
                            ]),

                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->minValue(0)
                            ->default(fn (): int => ((int) PaymentMethod::query()->max('sort_order')) + 1)
                            ->helperText('Angka kecil tampil lebih dulu di POS.'),
                    ]),
            ]);
    }
}