<?php

namespace Moox\BlockEditor\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Moox\BlockEditor\Models\Template;

class TemplatePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function view(Authenticatable $user, Template $template): bool
    {
        return $user !== null;
    }

    public function create(Authenticatable $user): bool
    {
        return $user !== null;
    }

    public function update(Authenticatable $user, Template $template): bool
    {
        return $user !== null;
    }

    public function delete(Authenticatable $user, Template $template): bool
    {
        return $user !== null;
    }
}
