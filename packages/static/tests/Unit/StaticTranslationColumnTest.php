<?php

declare(strict_types=1);

use Moox\Static\Filament\Tables\Columns\StaticTranslationColumn;

test('deprecated static translation column alias resolves to core view', function (): void {
    $reflection = new ReflectionClass(StaticTranslationColumn::class);

    expect(class_exists(StaticTranslationColumn::class))->toBeTrue()
        ->and($reflection->getDefaultProperties()['view'] ?? null)
        ->toBe('core::filament.tables.columns.translations');
});
