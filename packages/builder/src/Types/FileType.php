<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class FileType extends AbstractType
{
    protected array $availableFields = [
        'FileUpload',
        'FileUploadMultiple',
    ];

    protected string $defaultField = 'FileUpload';

    protected string $databaseType = 'string';
}
