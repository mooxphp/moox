<?php

declare(strict_types=1);

namespace Moox\Builder\Types;

class TextType extends AbstractType
{
    protected array $availableFields = [
        'Textarea',
        'RichEditor',
        'MarkdownEditor',
    ];

    protected string $defaultField = 'Textarea';

    protected string $databaseType = 'text';
}
