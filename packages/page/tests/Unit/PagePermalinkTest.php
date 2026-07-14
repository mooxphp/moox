<?php

declare(strict_types=1);

use Moox\Page\Models\Page;
use Moox\Page\Support\PagePermalink;

test('permalink from slug normalizes leading slash', function (): void {
    expect(PagePermalink::fromSlug('about'))->toBe('/about')
        ->and(PagePermalink::fromSlug('/about'))->toBe('/about');
});

test('lookup candidates include slug and legacy pages paths', function (): void {
    expect(PagePermalink::lookupCandidates('legacy-page'))->toBe([
        'legacy-page',
        '/legacy-page',
        'pages/legacy-page',
        '/pages/legacy-page',
    ]);
});
