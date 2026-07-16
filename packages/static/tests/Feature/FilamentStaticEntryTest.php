<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Moox\Static\Models\StaticEntry;
use Moox\Static\Resources\StaticEntryResource;
use Moox\Static\Tests\FeatureTestCase;

uses(FeatureTestCase::class);

it('registers the static entry resource on the admin panel', function (): void {
    $panel = Filament::getCurrentPanel();

    expect($panel)->not->toBeNull();
    expect($panel->hasPlugin('static'))->toBeTrue();
    expect(StaticEntryResource::getModel())->toBe(StaticEntry::class);
});

it('builds the static entry resource form schema', function (): void {
    $form = StaticEntryResource::form(Schema::make());

    expect($form)->toBeInstanceOf(Schema::class);
    expect(StaticEntryResource::getPages())->toHaveKeys(['index', 'create', 'edit', 'view']);
});
