<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Support\CustomFieldsTranslatability;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('treats non-translatable custom field resources as single-locale', function (): void {
    expect(app(CustomFieldsTranslatability::class)->forResource(TestItemResource::class))->toBeFalse()
        ->and(app(CustomFieldsTranslatability::class)->forEntity('item'))->toBeFalse()
        ->and(app(CustomFieldsTranslatability::class)->forModel(TestItemResource::getModel()))->toBeFalse();
});

it('detects translatable models via astrotomic contract', function (): void {
    expect(app(CustomFieldsTranslatability::class)->forModel(FieldGroup::class))->toBeTrue();
});
