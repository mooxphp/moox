<?php

namespace Moox\User\Support;

use Spatie\Permission\Traits\HasRoles;

if (trait_exists(HasRoles::class)) {
    class_alias(HasRoles::class, HasRolesTrait::class);
} else {
    trait HasRolesTrait
    {
        // Spatie permissions is optional. When not installed, roles are disabled.
    }
}
