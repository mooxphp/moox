<?php

declare(strict_types=1);

namespace Moox\KositValidator\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\KositValidator\Models\KositValidation;

class KositValidationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:KositValidation');
    }

    public function view(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('View:KositValidation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:KositValidation');
    }

    public function update(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('Update:KositValidation');
    }

    public function delete(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('Delete:KositValidation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:KositValidation');
    }

    public function restore(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('Restore:KositValidation');
    }

    public function forceDelete(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('ForceDelete:KositValidation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:KositValidation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:KositValidation');
    }

    public function replicate(AuthUser $authUser, KositValidation $kositValidation): bool
    {
        return $authUser->can('Replicate:KositValidation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:KositValidation');
    }
}
