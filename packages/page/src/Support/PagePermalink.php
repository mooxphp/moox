<?php

declare(strict_types=1);

namespace Moox\Page\Support;

class PagePermalink
{
    public static function fromSlug(string $slug): string
    {
        return '/'.ltrim($slug, '/');
    }

    /**
     * @return list<string>
     */
    public static function lookupCandidates(string $slug): array
    {
        $normalizedSlug = ltrim($slug, '/');

        return array_values(array_unique([
            $normalizedSlug,
            '/'.$normalizedSlug,
            'pages/'.$normalizedSlug,
            '/pages/'.$normalizedSlug,
        ]));
    }
}
