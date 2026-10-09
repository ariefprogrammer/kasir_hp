<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductUnit;
use App\Services\StockService;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UnitsRelationManager extends RelationManager
{
    protected static string $relationship = 'units';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Unit IMEI / Serial';
    }

    // Tab hanya muncul untuk produk yang diberi tanda "Catat IMEI".
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) $ownerRecord->track_imei;
    }

    private static function identifierRules(): array
    {
        return [
            fn (?ProductUnit $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                $message = ProductUnit::validateIdentifier($value, $record?->getKey());

                if ($message) {
                    $fail($message);
                }
            },
        ];
    }

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord();

        return $schema->components([
            TextInput::make('imei')
                ->label('IMEI 1 / Serial')
                ->requiredWithout('imei2')
                ->maxLength(30)
                ->autocomplete(false)
                ->autofocus()
                ->rules(self::identifierRules()),

            TextInput::make('imei2')
                ->label('IMEI 2 (opsional)')
                ->requiredWithout('imei')
                ->different('imei')
                ->maxLength(30)
                ->autocomplete(false)
                ->rules(self::identifierRules()),

            Textarea::make('note')
                ->label('Catatan')
                ->rows(2)
                ->maxLength(255),

            Toggle::make('adds_stock')
                ->label('Barang baru masuk (tambah stok)')
                ->helperText('Aktifkan bila unit ini belum termasuk stok yang tercatat. Biarkan mati saat Anda melengkapi IMEI untuk stok yang sudah ada.')
                ->default(false)
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->rules([
                    fn (): Closure => function (string $attribute, $value, Closure $fail) use ($owner) {
                        if ($value) {
                            return;
                        }

                        if ($owner->availableUnits()->count() >= $owner->stock) {
                            $fail('Semua stok sudah memiliki IMEI. Aktifkan "Barang baru masuk" bila ini unit tambahan.');
                        }
                    },
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Unit IMEI / Serial')
            ->description(fn (): string => $this->getOwnerRecord()->availableUnits()->count()
                . ' dari ' . $this->getOwnerRecord()->stock
                . ' unit tersedia sudah dicatat IMEI-nya')
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

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'tersedia' ? 'success' : 'gray'),

                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(['tersedia' => 'Tersedia', 'terjual' => 'Terjual']),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah unit')
                    ->using(function (array $data, string $model): Model {
                        $product = $this->getOwnerRecord();
                        $addsStock = (bool) ($data['adds_stock'] ?? false);
                        unset($data['adds_stock']);

                        return DB::transaction(function () use ($product, $data, $addsStock) {
                            $unit = $product->units()->create($data + ['status' => 'tersedia']);

                            if ($addsStock) {
                                app(StockService::class)->move(
                                    product: $product,
                                    qty: 1,
                                    type: 'in',
                                    reference: $unit->imei ?? $unit->imei2,
                                    note: 'Stok masuk dengan IMEI',
                                );
                            }

                            return $unit;
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (ProductUnit $record): bool => $record->status === 'tersedia'),

                // Menghapus catatan tidak mengubah stok. Untuk mengurangi stok, pakai "Sesuaikan stok".
                DeleteAction::make()
                    ->visible(fn (ProductUnit $record): bool => $record->status === 'tersedia'),
            ])
            ->defaultSort('id', 'desc');
    }
}