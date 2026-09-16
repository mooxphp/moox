<?php

declare(strict_types=1);

use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Mjml;
use Moox\Mjml\MjmlResult;
use Moox\Mjml\Renderers\NodeRenderer;
use Moox\Mjml\Renderers\PhpRenderer;

function sampleMjml(): string
{
    return <<<'MJML'
<mjml>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-text>Hello World</mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
MJML;
}

function invalidMjml(): string
{
    return '<mjml><mj-unknown /></mjml>';
}

function publicMjmlSources(): array
{
    $src = dirname(__DIR__, 2).'/src';

    return [
        $src.'/Mjml.php',
        $src.'/MjmlResult.php',
        $src.'/MjmlError.php',
        $src.'/Enums/ValidationLevel.php',
        $src.'/Contracts/MjmlRenderer.php',
        $src.'/Exceptions/CouldNotRenderMjml.php',
    ];
}

afterEach(function (): void {
    config()->set('mjml.use_php_renderer', true);
});

it('converts mjml to html with the php renderer by default', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $html = Mjml::new()->toHtml(sampleMjml());

    expect($html)->toContain('Hello World');
});

it('converts mjml to html with the node renderer when node and mjml are available', function (): void {
    config()->set('mjml.use_php_renderer', false);

    try {
        $html = Mjml::new()->toHtml(sampleMjml());
    } catch (Throwable $exception) {
        test()->markTestSkipped($exception->getMessage());
    }

    expect($html)->toContain('Hello World');
});

it('does not fall back to the node renderer when the php engine is missing', function (): void {
    $renderer = new PhpRenderer('ThisPhpMjmlEngineDoesNotExist');

    expect(fn (): string => $renderer->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class, 'shyim/mjml-php is required when mjml.use_php_renderer is true.');
});

it('does not fall back to the php renderer when the node engine is missing', function (): void {
    $renderer = new NodeRenderer('ThisNodeMjmlEngineDoesNotExist');

    expect(fn (): string => $renderer->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class, 'spatie/mjml-php is required when mjml.use_php_renderer is false.');
});

it('raises when the php renderer cannot convert mjml', function (): void {
    config()->set('mjml.use_php_renderer', true);

    expect(fn (): string => Mjml::new()->toHtml(invalidMjml()))
        ->toThrow(CouldNotRenderMjml::class);
});

it('returns a moox result from convert on the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $result = Mjml::new()->convert(sampleMjml());

    expect($result)->toBeInstanceOf(MjmlResult::class)
        ->and($result->html())->toContain('Hello World')
        ->and($result->hasErrors())->toBeFalse()
        ->and($result->errors())->toBe([])
        ->and($result->array())->toBe([]);
});

it('minifies html through the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $default = Mjml::new()->toHtml(sampleMjml());
    $minified = Mjml::new()->minify()->toHtml(sampleMjml());

    expect($minified)->toContain('Hello World');
    expect(strlen($minified))->toBeLessThan(strlen($default));
});

it('beautifies html through the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $beautified = Mjml::new()->beautify()->toHtml(sampleMjml());

    expect($beautified)
        ->toContain('Hello World')
        ->toContain(">\n");
});

it('hides comments through the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $html = Mjml::new()->hideComments()->toHtml(sampleMjml());

    expect($html)->toContain('Hello World');
});

it('forwards toHtml options on the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $minified = Mjml::new()->toHtml(sampleMjml(), ['minify' => true]);

    expect($minified)->toContain('Hello World');
    expect(strlen($minified))->toBeLessThan(strlen(Mjml::new()->toHtml(sampleMjml())));
});

it('collects validation errors in soft mode on the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $result = Mjml::new()->validationLevel(ValidationLevel::Soft)->convert(invalidMjml());

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors())->not->toBeEmpty();
});

it('reports whether mjml can convert on the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    expect(Mjml::new()->canConvert(sampleMjml()))->toBeTrue()
        ->and(Mjml::new()->canConvert(invalidMjml()))->toBeFalse()
        ->and(Mjml::new()->canConvertWithoutErrors(sampleMjml()))->toBeTrue()
        ->and(Mjml::new()->validationLevel(ValidationLevel::Soft)->canConvertWithoutErrors(invalidMjml()))->toBeFalse();
});

it('raises when sidecar is used with the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    expect(fn (): string => Mjml::new()->sidecar()->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class, 'sidecar() is only available when mjml.use_php_renderer is false.');
});

it('raises when workingDirectory is used with the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    expect(fn (): string => Mjml::new()->workingDirectory('/tmp')->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class, 'workingDirectory() is only available when mjml.use_php_renderer is false.');
});

it('converts with options on the node renderer when node and mjml are available', function (): void {
    config()->set('mjml.use_php_renderer', false);

    try {
        $result = Mjml::new()->minify()->convert(sampleMjml());
    } catch (Throwable $exception) {
        test()->markTestSkipped($exception->getMessage());
    }

    expect($result)->toBeInstanceOf(MjmlResult::class)
        ->and($result->html())->toContain('Hello World')
        ->and($result->hasErrors())->toBeFalse();
});

it('does not import vendor mjml types on the public api', function (): void {
    foreach (publicMjmlSources() as $path) {
        $source = file_get_contents($path);

        expect($source)
            ->not->toContain('use Spatie\\Mjml\\')
            ->not->toContain('use MjmlPHP\\');
    }
});
