<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProductUnit;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductUnitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ProductUnit');
    }

    public function view(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('View:ProductUnit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ProductUnit');
    }

    public function update(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('Update:ProductUnit');
    }

    public function delete(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('Delete:ProductUnit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ProductUnit');
    }

    public function restore(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('Restore:ProductUnit');
    }

    public function forceDelete(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('ForceDelete:ProductUnit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ProductUnit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ProductUnit');
    }

    public function replicate(AuthUser $authUser, ProductUnit $productUnit): bool
    {
        return $authUser->can('Replicate:ProductUnit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ProductUnit');
    }

}