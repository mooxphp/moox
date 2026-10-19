<?php

declare(strict_types=1);

namespace Moox\MailTesting\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\MailTesting\Models\MailTestingMessage;

class MailTestingMessagePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MailTestingMessage');
    }

    public function view(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('View:MailTestingMessage');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MailTestingMessage');
    }

    public function update(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('Update:MailTestingMessage');
    }

    public function delete(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('Delete:MailTestingMessage');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MailTestingMessage');
    }

    public function restore(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('Restore:MailTestingMessage');
    }

    public function forceDelete(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('ForceDelete:MailTestingMessage');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MailTestingMessage');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MailTestingMessage');
    }

    public function replicate(AuthUser $authUser, MailTestingMessage $mailTestingMessage): bool
    {
        return $authUser->can('Replicate:MailTestingMessage');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MailTestingMessage');
    }
}
