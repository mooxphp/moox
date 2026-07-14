<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

class TestTranslatableItem extends TestItem
{
    public static function customFieldsAreTranslatable(): bool
    {
        return true;
    }

    protected static function customFieldsEntity(): ?string
    {
        return 'translatable-item';
    }
}
