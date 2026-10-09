<?php

declare(strict_types=1);

namespace Moox\Company\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Company\Models\Company;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Company');
    }

    public function view(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('View:Company');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Company');
    }

    public function update(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Update:Company');
    }

    public function delete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Delete:Company');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Company');
    }

    public function restore(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Restore:Company');
    }

    public function forceDelete(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('ForceDelete:Company');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Company');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Company');
    }

    public function replicate(AuthUser $authUser, Company $company): bool
    {
        return $authUser->can('Replicate:Company');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Company');
    }

}