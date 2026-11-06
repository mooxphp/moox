<?php

declare(strict_types=1);

namespace Moox\EBilling\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\EBilling\Models\EbillingDocument;

class EbillingDocumentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EbillingDocument');
    }

    public function view(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('View:EbillingDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EbillingDocument');
    }

    public function update(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('Update:EbillingDocument');
    }

    public function delete(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('Delete:EbillingDocument');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:EbillingDocument');
    }

    public function restore(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('Restore:EbillingDocument');
    }

    public function forceDelete(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('ForceDelete:EbillingDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:EbillingDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:EbillingDocument');
    }

    public function replicate(AuthUser $authUser, EbillingDocument $ebillingDocument): bool
    {
        return $authUser->can('Replicate:EbillingDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:EbillingDocument');
    }
}
