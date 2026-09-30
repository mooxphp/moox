<?php

declare(strict_types=1);

use Moox\MailTesting\Support\HtmlLength;
use Tests\TestCase;

uses(TestCase::class);

it('counts bytes and unicode characters separately', function (): void {
    app()->setLocale('de');

    $html = '<html>Größe</html>';

    expect(HtmlLength::of($html))
        ->toBe([
            'bytes' => strlen($html),
            'characters' => mb_strlen($html, 'UTF-8'),
        ])
        ->and(HtmlLength::of($html)['bytes'])->toBeGreaterThan(HtmlLength::of($html)['characters'])
        ->and(HtmlLength::formatBytes(10436))->toBe('10.436 Bytes')
        ->and(HtmlLength::formatCharacters(10210))->toBe('10.210 Zeichen')
        ->and(HtmlLength::of(null))->toBe(['bytes' => 0, 'characters' => 0]);
});
