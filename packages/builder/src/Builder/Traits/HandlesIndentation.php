<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesIndentation
{
    protected function indent(string $content, int $level = 1): string
    {
        $indentation = str_repeat('    ', $level);

        return implode("\n", array_map(
            fn ($line) => $line !== '' ? $indentation.$line : $line,
            explode("\n", $content)
        ));
    }

    protected function formatWithIndentation(array $items, int $level = 1): string
    {
        if (empty($items)) {
            return '';
        }

        $indentation = str_repeat('    ', $level);
        $formattedItems = array_map(
            fn ($item) => $indentation.trim($item, "'"),
            $items
        );

        return implode(",\n", $formattedItems);
    }
}
