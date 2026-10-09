<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;
    protected bool $trackImeiChanged = false;

    protected function beforeSave(): void
    {
        // Bandingkan nilai toggle di form dengan nilai yang tersimpan sebelum diubah.
        $this->trackImeiChanged = (bool) $this->getRecord()->track_imei
            !== (bool) ($this->data['track_imei'] ?? false);
    }

    protected function afterSave(): void
    {
        // Muat ulang halaman hanya bila toggle berubah, supaya tab "Unit IMEI"
        // muncul atau hilang sesuai nilai terbaru.
        if ($this->trackImeiChanged) {
            $this->redirect(static::getResource()::getUrl('edit', ['record' => $this->getRecord()]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
