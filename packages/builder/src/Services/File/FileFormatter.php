<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use RuntimeException;

class FileFormatter
{
    public function formatFiles(array $paths): void
    {
        if (empty($paths)) {
            return;
        }

        $fileList = implode(' ', array_map(
            fn ($path) => escapeshellarg(str_replace('\\', '/', $path)),
            array_filter($paths)
        ));

        if (empty($fileList)) {
            return;
        }

        $command = PHP_OS_FAMILY === 'Windows'
            ? "php vendor/bin/pint {$fileList} --quiet"
            : "vendor/bin/pint {$fileList} --quiet";

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }
}
