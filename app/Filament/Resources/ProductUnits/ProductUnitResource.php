<?php

namespace App\Filament\Resources\ProductUnits;

use App\Filament\Resources\ProductUnits\Pages\ListProductUnits;
use App\Models\ProductUnit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductUnitResource extends Resource
{
    protected static ?string $model = ProductUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $modelLabel = 'Unit IMEI';

    protected static ?string $pluralModelLabel = 'Cari IMEI';

    protected static ?string $navigationLabel = 'Cari IMEI';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 3;

    // Semua pengguna aktif boleh mencari. Halaman ini hanya-baca.
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_active;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        // Kolom produk dipilih terbatas: harga modal tidak ikut dimuat.
        return parent::getEloquentQuery()->with([
            'product:id,name,sku',
            'saleItem.sale.cashier:id,name',
        ]);
    }

    public static function table(Table $table): Table
    {
        $manager = fn (): bool => auth()->user()->isManager();

        return $table
            ->columns([
                TextColumn::make('imei')
                    ->label('IMEI 1 / Serial')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                TextColumn::make('imei2')
                    ->label('IMEI 2')
                    ->searchable()
                    ->copyable()
                    ->placeholder('-'),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'tersedia' ? 'success' : 'gray'),

                // Rincian penjualan hanya untuk Owner/Admin, karena kasir hanya boleh melihat transaksinya sendiri.
                TextColumn::make('invoice')
                    ->label('Invoice')
                    ->state(fn (ProductUnit $record): ?string => $record->status === 'terjual'
                        ? $record->saleItem?->sale?->invoice_no
                        : null)
                    ->placeholder('-')
                    ->visible($manager),

                TextColumn::make('sold_time')
                    ->label('Waktu jual')
                    ->state(fn (ProductUnit $record): ?string => $record->status === 'terjual'
                        ? $record->saleItem?->sale?->sold_at?->format('d M Y H:i')
                        : null)
                    ->placeholder('-')
                    ->visible($manager),

                TextColumn::make('seller')
                    ->label('Kasir')
                    ->state(fn (ProductUnit $record): ?string => $record->status === 'terjual'
                        ? $record->saleItem?->sale?->cashier?->name
                        : null)
                    ->placeholder('-')
                    ->visible($manager),

                TextColumn::make('buyer')
                    ->label('Pembeli')
                    ->state(fn (ProductUnit $record): ?string => $record->status === 'terjual'
                        ? collect([$record->saleItem?->sale?->customer_name, $record->saleItem?->sale?->customer_phone])->filter()->implode(' / ')
                        : null)
                    ->placeholder('-')
                    ->visible($manager),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['tersedia' => 'Tersedia', 'terjual' => 'Terjual']),

                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label('Struk')
                    ->icon('heroicon-o-printer')
                    ->url(fn (ProductUnit $record): string => route('receipt.show', $record->saleItem->sale))
                    ->openUrlInNewTab()
                    ->visible(fn (ProductUnit $record): bool => $record->status === 'terjual'
                        && auth()->user()->isManager()
                        && $record->saleItem?->sale !== null),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductUnits::route('/'),
        ];
    }
}