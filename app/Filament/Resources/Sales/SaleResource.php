<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Actions\VoidSaleAction;
use App\Filament\Resources\Sales\Pages\ListSales;
use App\Filament\Resources\Sales\Pages\ViewSale;
use App\Models\Sale;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentMethod;
use UnitEnum;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Riwayat Transaksi';

    protected static ?string $navigationLabel = 'Riwayat Transaksi';

    protected static string|UnitEnum|null $navigationGroup = 'Penjualan';

    protected static ?int $navigationSort = 4;

    // Resource hanya-baca: transaksi dibuat dari halaman POS, dibatalkan lewat void.
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

    // Kasir hanya melihat transaksinya sendiri; Owner/Admin melihat semua.
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('cashier:id,name');

        $user = auth()->user();

        if (! $user->isManager()) {
            $query->where('cashier_id', $user->id);
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Transaksi')
                ->columns(3)
                ->schema([
                    TextEntry::make('invoice_no')->label('No. Invoice'),
                    TextEntry::make('sold_at')->label('Waktu')->dateTime('d M Y H:i'),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => $state === 'void' ? 'danger' : 'success'),
                    TextEntry::make('cashier.name')->label('Kasir'),
                    TextEntry::make('customer_name')->label('Pembeli')->placeholder('-'),
                    TextEntry::make('customer_phone')->label('No. HP')->placeholder('-'),
                ]),

            Section::make('Item')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->columns(4)
                        ->schema([
                            TextEntry::make('product_name')->label('Produk'),
                            TextEntry::make('imei')
                                ->label('IMEI')
                                ->placeholder('-'),

                            TextEntry::make('imei2')
                                ->label('IMEI 2')
                                ->placeholder('-'),
                            TextEntry::make('qty')->label('Qty'),
                            TextEntry::make('standard_price')
                                ->label('Harga standar')
                                ->formatStateUsing(fn ($state): string => rupiah($state)),
                            TextEntry::make('deal_price')
                                ->label('Harga deal')
                                ->formatStateUsing(fn ($state): string => rupiah($state)),
                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->formatStateUsing(fn ($state): string => rupiah($state)),
                        ]),
                ]),

            Section::make('Pembayaran')
                ->columns(4)
                ->schema([
                    TextEntry::make('total')
                        ->label('Total')
                        ->formatStateUsing(fn ($state): string => rupiah($state)),
                    TextEntry::make('payment_method')
                        ->label('Metode')
                        ->formatStateUsing(fn ($state): string => PaymentMethod::label((string) $state)),
                    TextEntry::make('paid_amount')
                        ->label('Dibayar')
                        ->formatStateUsing(fn ($state): string => rupiah($state)),
                    TextEntry::make('change_amount')
                        ->label('Kembalian')
                        ->formatStateUsing(fn ($state): string => rupiah($state)),
                ]),

            Section::make('Informasi Void')
                ->columns(3)
                ->visible(fn (?Sale $record): bool => $record?->status === 'void')
                ->schema([
                    TextEntry::make('void_reason')->label('Alasan'),
                    TextEntry::make('voidedBy.name')->label('Di-void oleh'),
                    TextEntry::make('voided_at')->label('Waktu void')->dateTime('d M Y H:i'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('No. Invoice')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sold_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->visible(fn (): bool => auth()->user()->isManager()),

                TextColumn::make('items_count')
                    ->label('Item')
                    ->counts('items'),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => rupiah($state))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->formatStateUsing(fn (string $state): string => PaymentMethod::label($state))
                    ->label('Bayar')
                    ->badge(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'void' ? 'danger' : 'success'),
            ])
            ->filters([
                Filter::make('sold_at')
                    ->label('Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari tanggal'),
                        DatePicker::make('until')->label('Sampai tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('sold_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('sold_at', '<=', $date))),

                SelectFilter::make('status')
                    ->options(['selesai' => 'Selesai', 'void' => 'Void']),

                SelectFilter::make('payment_method')
                    ->label('Metode bayar')
                    ->options(fn (): array => PaymentMethod::labels()),

                SelectFilter::make('cashier_id')
                    ->label('Kasir')
                    ->relationship('cashier', 'name')
                    ->visible(fn (): bool => auth()->user()->isManager()),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('receipt')
                    ->label('Struk')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Sale $record): string => route('receipt.show', $record))
                    ->openUrlInNewTab(),

                VoidSaleAction::make(),
            ])
            ->defaultSort('sold_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'view' => ViewSale::route('/{record}'),
        ];
    }
}