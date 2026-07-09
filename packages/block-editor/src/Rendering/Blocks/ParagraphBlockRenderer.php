<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Rendering\Blocks;

use Moox\BlockEditor\Rendering\Contracts\BlockRenderer;
use Moox\BlockEditor\Rendering\RenderContext;

final class ParagraphBlockRenderer implements BlockRenderer
{
    public function supports(string $type): bool
    {
        return $type === 'paragraph';
    }

    public function render(array $block, RenderContext $context): string
    {
        $content = (string) ($block['content'] ?? '');

        if (trim(strip_tags($content)) === '') {
            return '';
        }

        $classes = trim((string) ($block['classes'] ?? ''));
        $classAttribute = $classes !== '' ? ' class="'.e($classes).'"' : '';

        return '<div'.$classAttribute.'>'.$content.'</div>';
    }
}
