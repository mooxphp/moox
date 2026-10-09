<?php

declare(strict_types=1);

namespace Moox\Data\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Data\Models\StaticCertificateKind;

class StaticCertificateKindPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:StaticCertificateKind');
    }

    public function view(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('View:StaticCertificateKind');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:StaticCertificateKind');
    }

    public function update(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('Update:StaticCertificateKind');
    }

    public function delete(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('Delete:StaticCertificateKind');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:StaticCertificateKind');
    }

    public function restore(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('Restore:StaticCertificateKind');
    }

    public function forceDelete(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('ForceDelete:StaticCertificateKind');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:StaticCertificateKind');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:StaticCertificateKind');
    }

    public function replicate(AuthUser $authUser, StaticCertificateKind $staticCertificateKind): bool
    {
        return $authUser->can('Replicate:StaticCertificateKind');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:StaticCertificateKind');
    }
}
