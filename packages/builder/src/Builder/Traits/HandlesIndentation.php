<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesIndentation
{
    protected function formatWithIndentation(array $lines, int $level = 3): string
    {
        $indent = str_repeat(' ', $level * 4);

        return $indent.implode("\n".$indent, array_filter($lines));
    }
}
