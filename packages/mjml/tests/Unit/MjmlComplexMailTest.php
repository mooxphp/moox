<?php

declare(strict_types=1);

use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Mjml;

if (! function_exists('mjmlFixtureDirectory')) {
    function mjmlFixtureDirectory(): string
    {
        return dirname(__DIR__).DIRECTORY_SEPARATOR.'Fixtures';
    }
}

if (! function_exists('mjmlFixture')) {
    function mjmlFixture(string $name): string
    {
        $path = mjmlFixtureDirectory().DIRECTORY_SEPARATOR.$name;
        $contents = file_get_contents($path);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException("Missing MJML fixture [{$name}].");
        }

        return $contents;
    }
}

afterEach(function (): void {
    config()->set('mjml.use_php_renderer', true);
});

it('renders a login mail without leftover mjml', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->toHtml(mjmlFixture('login.mjml'));

    expect($html)
        ->toContain('<!doctype')
        ->toContain('Sign in')
        ->toContain('{displayName}')
        ->toContain('signature=abc123')
        ->toContain('Privacy')
        ->toContain('Example Street 1')
        ->not->toContain('<mjml')
        ->not->toContain('<mj-section');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('renders an invoice mail with table and wrapper', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->toHtml(mjmlFixture('invoice.mjml'));

    expect($html)
        ->toContain('<!doctype')
        ->toContain('{invoiceNumber}')
        ->toContain('Widget A')
        ->toContain('20.50')
        ->toContain('Open invoice')
        ->not->toContain('<mjml')
        ->not->toContain('<mj-table');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('minifies a login mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $default = Mjml::new()->toHtml(mjmlFixture('login.mjml'));
    $minified = Mjml::new()->minify()->toHtml(mjmlFixture('login.mjml'));

    expect($minified)
        ->toContain('Sign in')
        ->toContain('signature=abc123');
    expect(strlen($minified))->toBeLessThan(strlen($default));
})->with([
    'php' => [true],
    'node' => [false],
]);

it('beautifies a login mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->beautify()->toHtml(mjmlFixture('login.mjml'));

    expect($html)
        ->toContain('Sign in')
        ->toContain(">\n");
})->with([
    'php' => [true],
    'node' => [false],
]);

it('keeps comments on a login mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    expect(Mjml::new()->keepComments()->toHtml(mjmlFixture('login.mjml')))
        ->toContain('marker-comment')
        ->toContain('Sign in');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('strips comments on a login mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    expect(Mjml::new()->hideComments()->toHtml(mjmlFixture('login.mjml')))
        ->toContain('Sign in')
        ->not->toContain('marker-comment');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('minifies a login mail without comments', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->hideComments()->minify()->toHtml(mjmlFixture('login.mjml'));

    expect($html)
        ->toContain('Sign in')
        ->not->toContain('marker-comment');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('includes a footer partial in a login mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()
        ->filePath(mjmlFixtureDirectory())
        ->ignoreIncludes(false)
        ->toHtml(mjmlFixture('login-with-include.mjml'));

    expect($html)
        ->toContain('Sign in')
        ->toContain('INCLUDED-FOOTER')
        ->toContain('signature=abc123');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('skips the footer partial when ignoreIncludes is true', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()
        ->filePath(mjmlFixtureDirectory())
        ->ignoreIncludes(true)
        ->toHtml(mjmlFixture('login-with-include.mjml'));

    expect($html)
        ->toContain('Sign in')
        ->not->toContain('INCLUDED-FOOTER');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('applies skip soft and strict validation on an invoice fragment', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $mjml = mjmlFixture('invoice-invalid-attr.mjml');

    $skip = Mjml::new()->validationLevel(ValidationLevel::Skip)->convert($mjml);
    $soft = Mjml::new()->validationLevel(ValidationLevel::Soft)->convert($mjml);

    expect($skip->html())->toContain('{invoiceNumber}')
        ->and($skip->hasErrors())->toBeFalse();
    expect($soft->html())->toContain('{invoiceNumber}')
        ->and($soft->hasErrors())->toBeTrue();
    expect(fn (): string => Mjml::new()->validationLevel(ValidationLevel::Strict)->toHtml($mjml))
        ->toThrow(CouldNotRenderMjml::class);
})->with([
    'php' => [true],
    'node' => [false],
]);

it('forwards minify through the options array on an invoice mail', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $default = Mjml::new()->toHtml(mjmlFixture('invoice.mjml'));
    $minified = Mjml::new()->toHtml(mjmlFixture('invoice.mjml'), ['minify' => true]);

    expect($minified)->toContain('Widget A');
    expect(strlen($minified))->toBeLessThan(strlen($default));
})->with([
    'php' => [true],
    'node' => [false],
]);
