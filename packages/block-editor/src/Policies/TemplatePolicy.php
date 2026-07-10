<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Moox\BlockEditor\Models\Template;

class TemplatePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Template $template): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Template $template): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, Template $template): bool
    {
        return true;
    }
}
