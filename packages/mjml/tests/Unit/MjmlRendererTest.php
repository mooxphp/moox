<?php

declare(strict_types=1);

use Moox\Mjml\Enums\ValidationLevel;
use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Mjml;
use Moox\Mjml\MjmlResult;
use Moox\Mjml\Renderers\NodeRenderer;
use Moox\Mjml\Renderers\PhpRenderer;
use Spatie\Mjml\Mjml as SpatieMjml;
use Symfony\Component\Process\ExecutableFinder;

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

function softInvalidMjml(): string
{
    return <<<'MJML'
<mjml>
  <mj-body>
    <mj-section>
      <mj-column>
        <mj-text invalid-attr="x">Hello World</mj-text>
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
MJML;
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

if (! function_exists('mjmlSpatiePackagePath')) {
    function mjmlSpatiePackagePath(): string
    {
        $file = (new ReflectionClass(SpatieMjml::class))->getFileName();

        if (is_string($file)) {
            return dirname($file, 2);
        }

        if (function_exists('base_path')) {
            return base_path('vendor/spatie/mjml-php');
        }

        return '';
    }
}

if (! function_exists('nodeMjmlAvailable')) {
    function nodeMjmlAvailable(): bool
    {
        $extraDirectories = [
            '/usr/local/bin',
            '/opt/homebrew/bin',
        ];

        $nodePathFromEnv = getenv('MJML_NODE_PATH');

        if (is_string($nodePathFromEnv) && $nodePathFromEnv !== '') {
            array_unshift($extraDirectories, $nodePathFromEnv);
        }

        $node = (new ExecutableFinder)->find('node', extraDirs: $extraDirectories);

        if (! is_string($node) || $node === '') {
            return false;
        }

        $spatie = mjmlSpatiePackagePath();

        return is_file($spatie.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'mjml.mjs')
            && is_dir($spatie.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'mjml');
    }
}

if (! function_exists('skipUnlessNodeMjmlAvailable')) {
    function skipUnlessNodeMjmlAvailable(): void
    {
        if (nodeMjmlAvailable()) {
            return;
        }

        test()->markTestSkipped('Node MJML is not available (node binary, Spatie mjml.mjs, and node_modules/mjml).');
    }
}

if (! function_exists('skipUnlessMjmlEngineAvailable')) {
    function skipUnlessMjmlEngineAvailable(bool $usePhpRenderer): void
    {
        if ($usePhpRenderer) {
            return;
        }

        skipUnlessNodeMjmlAvailable();
    }
}

afterEach(function (): void {
    config()->set('mjml.use_php_renderer', true);
});

it('converts mjml to html', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->toHtml(sampleMjml());

    expect($html)->toContain('Hello World');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('returns a moox result from convert', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $result = Mjml::new()->convert(sampleMjml());

    expect($result)->toBeInstanceOf(MjmlResult::class)
        ->and($result->html())->toContain('Hello World')
        ->and($result->hasErrors())->toBeFalse()
        ->and($result->errors())->toBe([]);
})->with([
    'php' => [true],
    'node' => [false],
]);

it('minifies html', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $default = Mjml::new()->toHtml(sampleMjml());
    $minified = Mjml::new()->minify()->toHtml(sampleMjml());

    expect($minified)->toContain('Hello World');
    expect(strlen($minified))->toBeLessThan(strlen($default));
})->with([
    'php' => [true],
    'node' => [false],
]);

it('beautifies html', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $beautified = Mjml::new()->beautify()->toHtml(sampleMjml());

    expect($beautified)
        ->toContain('Hello World')
        ->toContain(">\n");
})->with([
    'php' => [true],
    'node' => [false],
]);

it('hides comments', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $html = Mjml::new()->hideComments()->toHtml(sampleMjml());

    expect($html)->toContain('Hello World');
})->with([
    'php' => [true],
    'node' => [false],
]);

it('forwards toHtml options', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $minified = Mjml::new()->toHtml(sampleMjml(), ['minify' => true]);

    expect($minified)->toContain('Hello World');
    expect(strlen($minified))->toBeLessThan(strlen(Mjml::new()->toHtml(sampleMjml())));
})->with([
    'php' => [true],
    'node' => [false],
]);

it('collects validation errors in soft mode', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    $result = Mjml::new()->validationLevel(ValidationLevel::Soft)->convert(softInvalidMjml());

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors())->not->toBeEmpty();
})->with([
    'php' => [true],
    'node' => [false],
]);

it('reports whether mjml can convert', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    expect(Mjml::new()->canConvert(sampleMjml()))->toBeTrue()
        ->and(Mjml::new()->canConvertWithoutErrors(sampleMjml()))->toBeTrue()
        ->and(Mjml::new()->validationLevel(ValidationLevel::Soft)->canConvertWithoutErrors(softInvalidMjml()))->toBeFalse();
})->with([
    'php' => [true],
    'node' => [false],
]);

it('selects the renderer from mjml.use_php_renderer', function (bool $usePhpRenderer): void {
    skipUnlessMjmlEngineAvailable($usePhpRenderer);
    config()->set('mjml.use_php_renderer', $usePhpRenderer);

    expect(Mjml::new()->toHtml(sampleMjml()))->toContain('Hello World');
})->with([
    'php' => [true],
    'node' => [false],
]);

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
    expect(Mjml::new()->canConvert(invalidMjml()))->toBeFalse();
});

it('returns an empty json ast from the php renderer', function (): void {
    config()->set('mjml.use_php_renderer', true);

    expect(Mjml::new()->convert(sampleMjml())->array())->toBe([]);
});

it('converts with the php renderer without requiring node', function (): void {
    config()->set('mjml.use_php_renderer', true);

    $previous = getenv('MJML_NODE_PATH');
    putenv('MJML_NODE_PATH='.sys_get_temp_dir().DIRECTORY_SEPARATOR.'mjml-missing-node');

    try {
        expect(Mjml::new()->toHtml(sampleMjml()))->toContain('Hello World');
    } finally {
        if ($previous === false) {
            putenv('MJML_NODE_PATH');
        } else {
            putenv('MJML_NODE_PATH='.$previous);
        }
    }
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

it('returns a json ast from the node renderer', function (): void {
    skipUnlessNodeMjmlAvailable();
    config()->set('mjml.use_php_renderer', false);

    expect(Mjml::new()->convert(sampleMjml())->array())->not->toBeEmpty();
});

it('converts with the node renderer when workingDirectory points at the spatie bin', function (): void {
    skipUnlessNodeMjmlAvailable();
    config()->set('mjml.use_php_renderer', false);

    $html = Mjml::new()
        ->workingDirectory(mjmlSpatiePackagePath().DIRECTORY_SEPARATOR.'bin')
        ->toHtml(sampleMjml());

    expect($html)->toContain('Hello World');
});

it('raises when the node renderer working directory is missing', function (): void {
    skipUnlessNodeMjmlAvailable();
    config()->set('mjml.use_php_renderer', false);

    $missing = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mjml-missing-bin';

    expect(fn (): string => Mjml::new()->workingDirectory($missing)->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class);
});

it('raises when sidecar is used on the node renderer without the sidecar package', function (): void {
    skipUnlessNodeMjmlAvailable();
    config()->set('mjml.use_php_renderer', false);

    expect(fn (): string => Mjml::new()->sidecar()->toHtml(sampleMjml()))
        ->toThrow(CouldNotRenderMjml::class);
})->skip(class_exists(\Spatie\MjmlSidecar\MjmlFunction::class), 'spatie/mjml-sidecar is installed');

it('does not import vendor mjml types on the public api', function (): void {
    foreach (publicMjmlSources() as $path) {
        $source = file_get_contents($path);

        expect($source)
            ->not->toContain('use Spatie\\Mjml\\')
            ->not->toContain('use MjmlPHP\\');
    }
});
