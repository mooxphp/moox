<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailTemplate\Models\MailLayout;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailLayoutPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailLayout');
    }

    public function view(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('View:MailLayout');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailLayout');
    }

    public function update(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('Update:MailLayout');
    }

    public function delete(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('Delete:MailLayout');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailLayout');
    }

    public function restore(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('Restore:MailLayout');
    }

    public function forceDelete(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('ForceDelete:MailLayout');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailLayout');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailLayout');
    }

    public function replicate(AuthUser $authUser, MailLayout $mailLayout): bool
    {
        return $authUser->can('Replicate:MailLayout');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailLayout');
    }

}