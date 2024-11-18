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
            fn ($path) => escapeshellarg($path),
            array_filter($paths)
        ));

        if (empty($fileList)) {
            return;
        }

        $command = "vendor/bin/pint {$fileList} --quiet";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }
}
