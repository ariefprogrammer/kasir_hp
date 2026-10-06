<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\AuditLog;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected array $oldRoles = [];

    protected function beforeSave(): void
    {
        $this->oldRoles = $this->record->roles()->pluck('name')->sort()->values()->all();
    }

    protected function afterSave(): void
    {
        $newRoles = $this->record->roles()->pluck('name')->sort()->values()->all();

        if ($newRoles !== $this->oldRoles) {
            AuditLog::record(
                'role_changed',
                $this->record,
                ['roles' => $this->oldRoles],
                ['roles' => $newRoles],
                $this->record->name,
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
