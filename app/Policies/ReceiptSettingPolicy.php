<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ReceiptSetting;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReceiptSettingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReceiptSetting');
    }

    public function view(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('View:ReceiptSetting');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReceiptSetting');
    }

    public function update(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('Update:ReceiptSetting');
    }

    public function delete(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('Delete:ReceiptSetting');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReceiptSetting');
    }

    public function restore(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('Restore:ReceiptSetting');
    }

    public function forceDelete(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('ForceDelete:ReceiptSetting');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReceiptSetting');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReceiptSetting');
    }

    public function replicate(AuthUser $authUser, ReceiptSetting $receiptSetting): bool
    {
        return $authUser->can('Replicate:ReceiptSetting');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReceiptSetting');
    }

}