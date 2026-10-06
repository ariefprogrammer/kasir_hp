<?php

namespace App\Filament\Actions;

use App\Models\Sale;
use App\Services\SaleService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use InvalidArgumentException;

class VoidSaleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'void';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Void')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Sale $record): bool => $record->status === 'selesai' && auth()->user()->isManager())
            ->modalHeading(fn (Sale $record): string => 'Void transaksi ' . $record->invoice_no)
            ->modalDescription('Stok akan dikembalikan dan transaksi tidak dihitung di laporan. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Void transaksi')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan void')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255),
            ])
            ->action(function (Sale $record, array $data): void {
                try {
                    app(SaleService::class)->void($record, auth()->user(), $data['reason']);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                $record->refresh();

                Notification::make()->title('Transaksi berhasil di-void')->success()->send();
            });
    }
}