<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticLanguage;

class StaticLanguagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticLanguage');
    }

    public function view(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('View:StaticLanguage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticLanguage');
    }

    public function update(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('Update:StaticLanguage');
    }

    public function delete(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('Delete:StaticLanguage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticLanguage');
    }

    public function restore(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('Restore:StaticLanguage');
    }

    public function forceDelete(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('ForceDelete:StaticLanguage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticLanguage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticLanguage');
    }

    public function replicate(AuthUser $authUser, StaticLanguage $staticLanguage): bool
    {
        return $authUser->can('Replicate:StaticLanguage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticLanguage');
    }
}
