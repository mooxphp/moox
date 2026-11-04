<?php

declare(strict_types=1);

namespace Moox\Organization\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Organization\Models\LegalForm;

class LegalFormPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LegalForm');
    }

    public function view(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('View:LegalForm');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LegalForm');
    }

    public function update(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('Update:LegalForm');
    }

    public function delete(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('Delete:LegalForm');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:LegalForm');
    }

    public function restore(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('Restore:LegalForm');
    }

    public function forceDelete(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('ForceDelete:LegalForm');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LegalForm');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LegalForm');
    }

    public function replicate(AuthUser $authUser, LegalForm $legalForm): bool
    {
        return $authUser->can('Replicate:LegalForm');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LegalForm');
    }
}
