<?php

namespace App\Filament\Resources\ReceiptSettings\Pages;

use App\Filament\Resources\ReceiptSettings\ReceiptSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceiptSettings extends ListRecords
{
    protected static string $resource = ReceiptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
