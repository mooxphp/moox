<?php

namespace Moox\Media\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Moox\Media\Models\Media;

class MediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(?Authenticatable $user): bool
    {
        return true;
    }

    public function view(?Authenticatable $user, Media $media): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Media $media): bool
    {
        if ($media->getOriginal('write_protected')) {
            return false;
        }

        return true;
    }

    public function delete(Authenticatable $user, Media $media): bool
    {
        if ($media->getOriginal('write_protected')) {
            return false;
        }

        return true;
    }
}
