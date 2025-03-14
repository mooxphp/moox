<?php

namespace Moox\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Moox\Media\Models\Media;
use Moox\User\Models\User;

class MediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Media $media): bool
    {
        if ($media->getOriginal('write_protected')) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Media $media): bool
    {
        if ($media->getOriginal('write_protected')) {
            return false;
        }

        return true;
    }
}
