<?php

declare(strict_types=1);

use Moox\Static\Filament\Tables\Columns\StaticTranslationColumn;
use Moox\Static\Tests\TestCase;

uses(TestCase::class);

test('static translation column uses the static translation view', function (): void {
    $reflection = new ReflectionClass(StaticTranslationColumn::class);

    expect($reflection->getDefaultProperties()['view'] ?? null)
        ->toBe('static::filament.tables.columns.translations');
});
