<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering;

use Moox\BlockEditor\Rendering\Contracts\BlockRenderer;
use Moox\BlockEditor\Support\BlockEditorLocale;

final class BlockContentRenderer
{
    /**
     * @param  iterable<BlockRenderer>  $renderers
     */
    public function __construct(
        private readonly iterable $renderers,
    ) {}

    public function render(mixed $content, ?string $locale = null): string
    {
        $locale ??= BlockEditorLocale::resolveActive();
        $blocks = $this->normalizeContent($content);

        return $this->renderBlocks($blocks, new RenderContext($locale));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeContent(mixed $content): array
    {
        if (is_string($content)) {
            $decoded = json_decode($content, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_array($content)) {
            return $content;
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    private function renderBlocks(array $blocks, RenderContext $context): string
    {
        return collect($blocks)
            ->map(fn (array $block): string => $this->renderBlock($block, $context))
            ->filter(fn (string $html): bool => $html !== '')
            ->implode("\n");
    }

    /**
     * @param  array<string, mixed>  $block
     */
    private function renderBlock(array $block, RenderContext $context): string
    {
        $type = (string) ($block['type'] ?? '');

        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($type)) {
                return $renderer->render($block, $context);
            }
        }

        if (isset($block['children']) && is_array($block['children'])) {
            return $this->renderBlocks($block['children'], $context);
        }

        return '';
    }
}
