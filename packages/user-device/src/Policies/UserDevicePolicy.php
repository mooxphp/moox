<?php

declare(strict_types=1);

namespace Moox\UserDevice\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Moox\UserDevice\Support\UserDeviceAuthorization;

class UserDevicePolicy
{
    public function viewAny(Authorizable $user): bool
    {
        return UserDeviceAuthorization::canViewAny($user);
    }

    public function view(Authorizable $user, Model $record): bool
    {
        if (UserDeviceAuthorization::canViewAll($user)) {
            return true;
        }

        return UserDeviceAuthorization::can($user, UserDeviceAuthorization::VIEW)
            && UserDeviceAuthorization::ownsRecord($user, $record);
    }

    public function create(Authorizable $user): bool
    {
        return false;
    }

    public function update(Authorizable $user, Model $record): bool
    {
        if (UserDeviceAuthorization::canViewAll($user)) {
            return true;
        }

        return UserDeviceAuthorization::can($user, UserDeviceAuthorization::UPDATE)
            && UserDeviceAuthorization::ownsRecord($user, $record);
    }

    public function delete(Authorizable $user, Model $record): bool
    {
        if (UserDeviceAuthorization::canViewAll($user)) {
            return true;
        }

        if (UserDeviceAuthorization::allowOpsWithoutShield()) {
            return true;
        }

        return UserDeviceAuthorization::can($user, UserDeviceAuthorization::DELETE)
            && UserDeviceAuthorization::ownsRecord($user, $record);
    }

    public function deleteAny(Authorizable $user): bool
    {
        if (UserDeviceAuthorization::allowOpsWithoutShield()) {
            return true;
        }

        return UserDeviceAuthorization::canViewAll($user);
    }
}
