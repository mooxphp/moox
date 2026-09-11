<?php

declare(strict_types=1);

use Moox\Mjml\Exceptions\CouldNotRenderMjml;
use Moox\Mjml\Mjml;
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

    expect(fn (): string => Mjml::new()->toHtml('<mjml><mj-unknown /></mjml>'))
        ->toThrow(CouldNotRenderMjml::class);
});
