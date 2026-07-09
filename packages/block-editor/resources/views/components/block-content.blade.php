@props([
    'content' => [],
    'locale' => null,
])

{!! app(\Moox\BlockEditor\Rendering\BlockContentRenderer::class)->render($content, $locale) !!}
