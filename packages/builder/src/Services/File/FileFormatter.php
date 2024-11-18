<?php

declare(strict_types=1);

namespace Moox\Builder\Services\File;

use RuntimeException;

class FileFormatter
{
    public function formatFiles(array $files): void
    {
        if (empty($files)) {
            return;
        }

        $fileList = implode(' ', array_map(
            fn ($file) => escapeshellarg($file),
            $files
        ));

        $command = "vendor/bin/pint {$fileList}";
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Pint formatting failed: '.implode("\n", $output));
        }
    }
}
