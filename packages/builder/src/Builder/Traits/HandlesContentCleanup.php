<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Traits;

trait HandlesContentCleanup
{
    protected function cleanupContent(string $content, string $classType = 'Model'): string
    {
        // Remove empty arrays
        $content = preg_replace("/\[\n                \n            \]/", '[]', $content);

        // Remove empty casts if exists
        $content = preg_replace("/\n\n    protected \\\$casts = \[\n        \n    \];\n/", '', $content);

        // Remove empty lines between form/table setup and return
        $content = preg_replace("/\{\n        \n\n        return/", "{\n        return", $content);

        // Remove empty lines after class opening brace
        $content = preg_replace("/class (.+) extends .*\n{(\n+)/", "class $1 extends $classType\n{\n", $content);

        // Remove empty line at the end of the class
        $content = preg_replace("/\n\n}/", "\n}", $content);

        // Remove multiple empty lines
        $content = preg_replace("/\n\n\n+/", "\n\n", $content);

        return $content;
    }
}
