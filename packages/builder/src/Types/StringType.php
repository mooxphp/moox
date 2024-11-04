<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class StringType extends AbstractType
{
    protected array $availableFields = [
        'TextInput',
        'Textarea',
        'RichEditor',
        'MarkdownEditor',
        'Hidden',
        'Select',
        'Radio',
        'ColorPicker',
    ];

    protected string $defaultField = 'TextInput';

    protected string $databaseType = 'string';
}
