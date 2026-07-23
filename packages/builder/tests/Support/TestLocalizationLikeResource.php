<?php

declare(strict_types=1);

namespace Moox\Builder\Tests\Support;

/**
 * Simulates resources such as LocalizationResource where Filament has no
 * recordTitleAttribute and getRecordTitle() would fall back to the model label.
 */
class TestLocalizationLikeResource extends TestItemResource
{
    protected static ?string $recordTitleAttribute = null;

    public static function getModelLabel(): string
    {
        return 'Localization';
    }
}
