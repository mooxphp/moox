<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticIcdScheme;

class StaticIcdSchemePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticIcdScheme');
    }

    public function view(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('View:StaticIcdScheme');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticIcdScheme');
    }

    public function update(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('Update:StaticIcdScheme');
    }

    public function delete(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('Delete:StaticIcdScheme');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticIcdScheme');
    }

    public function restore(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('Restore:StaticIcdScheme');
    }

    public function forceDelete(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('ForceDelete:StaticIcdScheme');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticIcdScheme');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticIcdScheme');
    }

    public function replicate(AuthUser $authUser, StaticIcdScheme $staticIcdScheme): bool
    {
        return $authUser->can('Replicate:StaticIcdScheme');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticIcdScheme');
    }
}
