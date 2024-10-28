<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesDuplication
{
    protected function uniqueUseStatements(array $statements): array
    {
        $statements = array_unique(array_filter($statements));
        sort($statements);

        $normalized = [];
        foreach ($statements as $statement) {
            $className = $this->extractClassName($statement);
            $normalized[$className] = $statement;
        }

        return array_values($normalized);
    }

    protected function uniqueTraits(array $traits): array
    {
        return array_values(array_unique(array_filter($traits)));
    }

    protected function uniqueMethods(array $methods): array
    {
        return array_values(array_unique(array_filter($methods)));
    }

    private function extractClassName(string $useStatement): string
    {
        preg_match('/use\s+(.+?);/', $useStatement, $matches);

        return $matches[1] ?? $useStatement;
    }
}
