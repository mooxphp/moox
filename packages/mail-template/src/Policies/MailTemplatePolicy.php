<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailTemplate\Models\MailTemplate;
use Illuminate\Auth\Access\HandlesAuthorization;

class MailTemplatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailTemplate');
    }

    public function view(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('View:MailTemplate');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailTemplate');
    }

    public function update(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('Update:MailTemplate');
    }

    public function delete(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('Delete:MailTemplate');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailTemplate');
    }

    public function restore(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('Restore:MailTemplate');
    }

    public function forceDelete(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('ForceDelete:MailTemplate');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailTemplate');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailTemplate');
    }

    public function replicate(AuthUser $authUser, MailTemplate $mailTemplate): bool
    {
        return $authUser->can('Replicate:MailTemplate');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailTemplate');
    }

}