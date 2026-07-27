<?php

declare(strict_types=1);

use Moox\Core\Entities\Items\Static\Tables\Columns\StaticTranslationColumn;

test('static translation column uses the localization translation view', function (): void {
    $reflection = new ReflectionClass(StaticTranslationColumn::class);

    expect($reflection->getDefaultProperties()['view'] ?? null)
        ->toBe('localization::filament.tables.columns.translations');
});
