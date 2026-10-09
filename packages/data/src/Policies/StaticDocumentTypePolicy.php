<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticDocumentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaticDocumentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticDocumentType');
    }

    public function view(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('View:StaticDocumentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticDocumentType');
    }

    public function update(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('Update:StaticDocumentType');
    }

    public function delete(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('Delete:StaticDocumentType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticDocumentType');
    }

    public function restore(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('Restore:StaticDocumentType');
    }

    public function forceDelete(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('ForceDelete:StaticDocumentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticDocumentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticDocumentType');
    }

    public function replicate(AuthUser $authUser, StaticDocumentType $staticDocumentType): bool
    {
        return $authUser->can('Replicate:StaticDocumentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticDocumentType');
    }

}