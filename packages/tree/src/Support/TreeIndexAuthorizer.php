<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Support;

use Heco\FilamentTreeIndex\Config\TreeIndexConfiguration;
use Illuminate\Support\Facades\Gate;

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
