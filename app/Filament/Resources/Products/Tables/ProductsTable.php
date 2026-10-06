<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use App\Services\StockService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('Foto')
                    ->disk('public')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('cost_price')
                    ->label('Modal')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->sortable()
                    ->visible(fn (): bool => auth()->user()->canViewCost()),

                TextColumn::make('sell_price')
                    ->label('Harga jual')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->badge()
                    ->sortable()
                    ->color(fn (Product $record): string => match (true) {
                        $record->stock <= 0 => 'danger',
                        $record->stock <= $record->min_stock => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),

                Filter::make('low_stock')
                    ->label('Stok menipis')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereColumn('stock', '<=', 'min_stock')),

                TernaryFilter::make('is_active')
                    ->label('Status aktif'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('adjustStock')
                    ->label('Sesuaikan stok')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()->canViewCost())
                    ->modalHeading(fn (Product $record): string => 'Sesuaikan stok: ' . $record->name)
                    ->schema([
                        Select::make('type')
                            ->label('Jenis')
                            ->options([
                                'in' => 'Stok masuk',
                                'adjustment' => 'Koreksi stok',
                            ])
                            ->default('in')
                            ->required()
                            ->live(),

                        TextInput::make('qty')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->rule('not_in:0')
                            ->minValue(fn (Get $get): ?int => $get('type') === 'in' ? 1 : null)
                            ->helperText('Untuk koreksi, gunakan angka negatif untuk mengurangi stok.'),

                        Textarea::make('note')
                            ->label('Alasan / catatan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (Product $record, array $data): void {
                        try {
                            app(StockService::class)->move(
                                product: $record,
                                qty: (int) $data['qty'],
                                type: $data['type'],
                                note: $data['note'],
                            );
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Stok berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}