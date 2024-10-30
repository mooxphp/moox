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

        // Remove empty traits
        $content = preg_replace("/\n    \n/", "\n", $content);

        // Remove multiple empty lines
        $content = preg_replace("/\n\n\n+/", "\n\n", $content);

        // Remove empty line after class opening brace for all class types
        $content = preg_replace("/class (.+) extends .*\n{(\n+)/", "class $1 extends $classType\n{\n", $content);

        // Remove empty line at the end of the class
        $content = preg_replace("/\n\n}/", "\n}", $content);

        return $content;
    }
}
