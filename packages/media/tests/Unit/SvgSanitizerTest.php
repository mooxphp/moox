<?php

declare(strict_types=1);

use Moox\Media\Support\SvgSanitizer;
use Tests\TestCase;

uses(TestCase::class);

it('strips scripts and event handlers from svg markup', function (): void {
    $dirty = <<<'SVG'
        <?xml version="1.0"?>
        <svg xmlns="http://www.w3.org/2000/svg" onclick="alert(1)">
          <script>alert(1)</script>
          <circle cx="10" cy="10" r="5" />
          <image xlink:href="https://evil.example/x.png" />
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('onclick=')
        ->and($clean)->not->toContain('https://evil.example')
        ->and($clean)->toContain('<circle');
});

it('strips data and blob href payloads', function (): void {
    $dirty = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg">
          <image href="data:image/svg+xml;base64,PHN2Zz48c2NyaXB0PmFsZXJ0KDEpPC9zY3JpcHQ+PC9zdmc+" />
          <a href="blob:https://evil.example/uuid"><circle cx="1" cy="1" r="1" /></a>
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('data:')
        ->and($clean)->not->toContain('blob:')
        ->and($clean)->toContain('<circle');
});

it('strips javascript hrefs', function (): void {
    $dirty = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg">
          <a href="javascript:alert(1)"><text>x</text></a>
          <a xlink:href="javascript:alert(2)"><text>y</text></a>
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('<text');
});

it('strips smil animate and set that retarget href', function (): void {
    $dirty = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg">
          <a href="#safe"><text>ok</text>
            <set attributeName="href" to="javascript:alert(1)" />
            <animate attributeName="xlink:href" values="javascript:alert(2)" />
          </a>
          <rect width="10" height="10" />
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('<set')
        ->and($clean)->not->toContain('<animate')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('<rect');
});

it('strips style attributes that use url()', function (): void {
    $dirty = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg">
          <rect style="fill:url(https://evil.example/x)" width="10" height="10" />
          <circle cx="5" cy="5" r="2" fill="#000" />
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('style=')
        ->and($clean)->not->toContain('evil.example')
        ->and($clean)->toContain('<circle');
});

it('strips external use elements', function (): void {
    $dirty = <<<'SVG'
        <svg xmlns="http://www.w3.org/2000/svg">
          <use href="https://evil.example/sprite.svg#icon" />
          <circle cx="5" cy="5" r="2" />
        </svg>
        SVG;

    $clean = app(SvgSanitizer::class)->sanitize($dirty);

    expect($clean)->not->toContain('evil.example')
        ->and($clean)->toContain('<circle');
});

it('rejects unparseable svg markup', function (): void {
    expect(fn () => app(SvgSanitizer::class)->sanitize('<not-svg>broken'))
        ->toThrow(RuntimeException::class);
});
