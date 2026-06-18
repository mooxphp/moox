<?php

declare(strict_types=1);

namespace Moox\Tree\Support;

use Filament\Resources\Pages\PageRegistration;
use Moox\Tree\Config\TreeIndexConfiguration;

final class TreeIndexResourcePages
{
    /**
     * @return class-string|null
     */
    public static function resolveCreatePageClass(TreeIndexConfiguration $configuration): ?string
    {
        $resourceClass = $configuration->getSourceResourceClass();

        if ($resourceClass === null) {
            return null;
        }

        /** @var array<string, mixed> $pages */
        $pages = $resourceClass::getPages();
        $registration = $pages['create'] ?? null;

        if (! $registration instanceof PageRegistration) {
            return null;
        }

        return $registration->getPage();
    }
}
