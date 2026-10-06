<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Actions\VoidSaleAction;
use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSale extends ViewRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')
                ->label('Cetak struk')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('receipt.show', $this->record))
                ->openUrlInNewTab(),

            VoidSaleAction::make(),
        ];
    }
}