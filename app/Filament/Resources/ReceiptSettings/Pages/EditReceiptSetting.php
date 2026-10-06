<?php

namespace App\Filament\Resources\ReceiptSettings\Pages;

use App\Filament\Resources\ReceiptSettings\ReceiptSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceiptSetting extends EditRecord
{
    protected static string $resource = ReceiptSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
