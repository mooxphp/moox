<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use RuntimeException;

class FileFormatter
{
    public function formatFiles(array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $fileList = implode(' ', array_map(
            fn ($path): string => escapeshellarg(str_replace('\\', '/', $path)),
            array_filter($paths)
        ));

        if ($fileList === '' || $fileList === '0') {
            return;
        }

        $command = PHP_OS_FAMILY === 'Windows'
            ? sprintf('php vendor/bin/pint %s --quiet', $fileList)
            : sprintf('vendor/bin/pint %s --quiet', $fileList);

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }
}
