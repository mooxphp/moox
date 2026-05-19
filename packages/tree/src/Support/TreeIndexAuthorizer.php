<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Illuminate\Support\Facades\Gate;
use Moox\Tree\Config\TreeIndexConfiguration;

final class TreeIndexAuthorizer
{
    public function __construct(private readonly TreeIndexConfiguration $configuration) {}

    public function authorize(): void
    {
        $ability = $this->configuration->getAuthorizationAbility();

        if (filled($ability)) {
            Gate::authorize((string) $ability, $this->configuration->modelClass());

            return;
        }

        abort_unless(auth()->check(), 403);
    }
}
