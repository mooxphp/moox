<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\VeraPdf\Models\VeraPdfValidation;

class VeraPdfValidationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:VeraPdfValidation');
    }

    public function view(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('View:VeraPdfValidation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:VeraPdfValidation');
    }

    public function update(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('Update:VeraPdfValidation');
    }

    public function delete(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('Delete:VeraPdfValidation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:VeraPdfValidation');
    }

    public function restore(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('Restore:VeraPdfValidation');
    }

    public function forceDelete(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('ForceDelete:VeraPdfValidation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:VeraPdfValidation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:VeraPdfValidation');
    }

    public function replicate(AuthUser $authUser, VeraPdfValidation $veraPdfValidation): bool
    {
        return $authUser->can('Replicate:VeraPdfValidation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:VeraPdfValidation');
    }
}
