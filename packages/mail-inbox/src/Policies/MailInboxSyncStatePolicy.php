<?php

declare(strict_types=1);

namespace Moox\MailInbox\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailInbox\Models\MailInboxSyncState;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailInboxSyncStatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailInboxSyncState');
    }

    public function view(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('View:MailInboxSyncState');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailInboxSyncState');
    }

    public function update(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('Update:MailInboxSyncState');
    }

    public function delete(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('Delete:MailInboxSyncState');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailInboxSyncState');
    }

    public function restore(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('Restore:MailInboxSyncState');
    }

    public function forceDelete(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('ForceDelete:MailInboxSyncState');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailInboxSyncState');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailInboxSyncState');
    }

    public function replicate(AuthUser $authUser, MailInboxSyncState $mailInboxSyncState): bool
    {
        return $authUser->can('Replicate:MailInboxSyncState');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailInboxSyncState');
    }

}