<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Lang;
use Moox\Support\Filament\FeatureFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
});

it('prefers entity translations over support translations', function () {
    Lang::addLines([
        'fields.is_active' => 'Product active',
    ], 'en', 'product');

    Lang::addLines([
        'active.forms.is_active' => 'Active',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'is_active'))
        ->toBe('Product active');
});

it('uses the support feature translation as fallback', function () {
    Lang::addLines([
        'active.forms.is_active' => 'Active',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'is_active'))
        ->toBe('Active');
});

it('uses the requested translation layer', function () {
    Lang::addLines([
        'active.columns.is_active' => 'Active column',
        'active.forms.is_active' => 'Active form',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'is_active', null, 'columns'))
        ->toBe('Active column');
});

it('falls back to forms when the requested layer is unavailable', function () {
    Lang::addLines([
        'active.forms.is_active' => 'Active form',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'is_active', null, 'columns'))
        ->toBe('Active form');
});

it('detects the feature automatically from the field key', function () {
    Lang::addLines([
        'external.forms.external_reference' => 'External reference',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'external_reference'))
        ->toBe('External reference');
});

it('uses an explicitly provided feature', function () {
    Lang::addLines([
        'custom.forms.example' => 'Custom example',
    ], 'en', 'support');

    expect(FeatureFields::label('product::fields', 'example', 'custom'))
        ->toBe('Custom example');
});

it('returns the entity translation key when no translation exists', function () {
    expect(FeatureFields::label('product::fields', 'unknown'))
        ->toBe('product::fields.unknown');
});

it('creates a locked text input', function () {
    Lang::addLines([
        'external.forms.external_reference' => 'External reference',
    ], 'en', 'support');

    $field = FeatureFields::locked('product::fields', 'external_reference');

    expect($field)
        ->toBeInstanceOf(TextInput::class)
        ->and($field->getName())->toBe('external_reference')
        ->and($field->getLabel())->toBe('External reference')
        ->and($field->isDisabled())->toBeTrue()
        ->and($field->isDehydrated())->toBeFalse();
});