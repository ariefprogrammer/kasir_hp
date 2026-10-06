<?php

namespace App\Filament\Resources\ReceiptSettings\Tables;

use App\Models\ReceiptSetting;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->square(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                TextColumn::make('store_name')
                    ->label('Nama toko')
                    ->searchable(),

                TextColumn::make('paper_type')
                    ->label('Kertas')
                    ->badge()
                    ->formatStateUsing(fn (ReceiptSetting $record): string => match ($record->paper_type) {
                        '58' => '58 mm',
                        '80' => '80 mm',
                        'a5' => 'A5',
                        default => 'Kustom ' . $record->custom_width_mm . ' mm',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('activate')
                    ->label('Jadikan aktif')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ReceiptSetting $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->modalDescription('Pengaturan ini akan dipakai untuk semua struk berikutnya.')
                    ->action(function (ReceiptSetting $record): void {
                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Pengaturan struk diaktifkan')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make()
                    ->hidden(fn (ReceiptSetting $record): bool => $record->is_active),
            ])
            ->defaultSort('is_active', 'desc');
    }
}