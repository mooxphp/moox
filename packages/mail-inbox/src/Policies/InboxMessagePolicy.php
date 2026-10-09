<?php

declare(strict_types=1);

namespace Moox\MailInbox\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailInbox\Models\InboxMessage;

class InboxMessagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:InboxMessage');
    }

    public function view(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('View:InboxMessage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:InboxMessage');
    }

    public function update(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('Update:InboxMessage');
    }

    public function delete(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('Delete:InboxMessage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:InboxMessage');
    }

    public function restore(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('Restore:InboxMessage');
    }

    public function forceDelete(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('ForceDelete:InboxMessage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:InboxMessage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:InboxMessage');
    }

    public function replicate(AuthUser $authUser, InboxMessage $inboxMessage): bool
    {
        return $authUser->can('Replicate:InboxMessage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:InboxMessage');
    }
}
