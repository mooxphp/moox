<?php

declare(strict_types=1);

namespace Moox\Prompts\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Moox\Prompts\Models\CommandExecution;

class CommandExecutionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:CommandExecution');
    }

    public function view(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('View:CommandExecution');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:CommandExecution');
    }

    public function update(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('Update:CommandExecution');
    }

    public function delete(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('Delete:CommandExecution');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:CommandExecution');
    }

    public function restore(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('Restore:CommandExecution');
    }

    public function forceDelete(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('ForceDelete:CommandExecution');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:CommandExecution');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:CommandExecution');
    }

    public function replicate(AuthUser $authUser, CommandExecution $commandExecution): bool
    {
        return $authUser->can('Replicate:CommandExecution');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:CommandExecution');
    }
}
