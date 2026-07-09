<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering\Blocks;

use Moox\BlockEditor\Rendering\Contracts\BlockRenderer;
use Moox\BlockEditor\Rendering\RenderContext;

final class HeadingBlockRenderer implements BlockRenderer
{
    public function supports(string $type): bool
    {
        return str_starts_with($type, 'heading') && strlen($type) === 8;
    }

    public function render(array $block, RenderContext $context): string
    {
        $level = (int) substr((string) ($block['type'] ?? 'heading1'), -1);
        $level = max(1, min(6, $level));
        $tag = 'h'.$level;

        $content = trim(strip_tags((string) ($block['content'] ?? '')));

        if ($content === '') {
            return '';
        }

        $classes = trim((string) ($block['classes'] ?? ''));
        $classAttribute = $classes !== '' ? ' class="'.e($classes).'"' : '';

        return '<'.$tag.$classAttribute.'>'.e($content).'</'.$tag.'>';
    }
}
