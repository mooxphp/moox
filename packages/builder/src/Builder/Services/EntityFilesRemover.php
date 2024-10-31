<?php

declare(strict_types=1);

namespace Moox\Builder\Builder\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class EntityFilesRemover extends AbstractService
{
    public function execute(): void
    {
        $this->removeFiles();
        if ($this->context->isPreview()) {
            $this->dropTable();
        }
    }

    private function removeFiles(): void
    {
        $paths = [
            $this->context->getModelPath(),
            $this->context->getResourcePath(),
            $this->context->getPluginPath(),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    private function dropTable(): void
    {
        Schema::dropIfExists($this->context->getTableName());
    }
}
