<?php

namespace Moox\Sync\Resolver;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Moox\Sync\Interfaces\FileResolverInterface;

abstract class AbstractFileResolver implements FileResolverInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function resolve(): array
    {
        $fileFields = $this->getFileFields();
        $resolvedFiles = [];

        foreach ($fileFields as $field) {
            $fileData = $this->getFileData($field);
            if ($fileData) {
                $resolvedFiles[$field] = $fileData;
            }
        }

        return $resolvedFiles;
    }

    abstract public function getFileFields(): array;

    public function getFileData(string $field): ?array
    {
        $filePath = $this->model->$field;

        if (! $filePath || ! Storage::exists($filePath)) {
            return null;
        }

        return [
            'path' => $filePath,
            'size' => Storage::size($filePath),
            'last_modified' => Storage::lastModified($filePath),
            'mime_type' => Storage::mimeType($filePath),
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
        ];
    }
}
