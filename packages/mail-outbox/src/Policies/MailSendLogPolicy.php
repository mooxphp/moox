<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailOutbox\Models\MailSendLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailSendLogPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailSendLog');
    }

    public function view(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('View:MailSendLog');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailSendLog');
    }

    public function update(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('Update:MailSendLog');
    }

    public function delete(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('Delete:MailSendLog');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailSendLog');
    }

    public function restore(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('Restore:MailSendLog');
    }

    public function forceDelete(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('ForceDelete:MailSendLog');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailSendLog');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailSendLog');
    }

    public function replicate(AuthUser $authUser, MailSendLog $mailSendLog): bool
    {
        return $authUser->can('Replicate:MailSendLog');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailSendLog');
    }

}