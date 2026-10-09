<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticCountry;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaticCountryPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticCountry');
    }

    public function view(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('View:StaticCountry');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticCountry');
    }

    public function update(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('Update:StaticCountry');
    }

    public function delete(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('Delete:StaticCountry');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticCountry');
    }

    public function restore(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('Restore:StaticCountry');
    }

    public function forceDelete(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('ForceDelete:StaticCountry');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticCountry');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticCountry');
    }

    public function replicate(AuthUser $authUser, StaticCountry $staticCountry): bool
    {
        return $authUser->can('Replicate:StaticCountry');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticCountry');
    }

}