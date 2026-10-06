<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\AuditLog;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        AuditLog::record(
            'role_changed',
            $this->record,
            null,
            ['roles' => $this->record->roles()->pluck('name')->sort()->values()->all()],
            $this->record->name,
        );
    }
}
