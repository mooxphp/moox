<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItemResource.php';

uses(Moox\Builder\Tests\TestCase::class);

use Moox\Builder\Registry\EntityRegistry;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

it('resolves entity keys from registered resources', function (): void {
    config()->set('builder.entities', [
        'item' => [
            'resource' => TestItemResource::class,
            'label' => 'Items',
        ],
    ]);

    $registry = app(EntityRegistry::class);

    expect($registry->resolveForResource(TestItemResource::class))->toBe('item')
        ->and($registry->resourceFor('item'))->toBe(TestItemResource::class)
        ->and($registry->optionsForSelect())->toBe(['item' => 'Items'])
        ->and($registry->isRegisteredResource(TestItemResource::class))->toBeTrue();
});

it('returns null for unregistered resources', function (): void {
    config()->set('builder.entities', []);

    $registry = app(EntityRegistry::class);

    expect($registry->resolveForResource(TestItemResource::class))->toBeNull()
        ->and($registry->isRegisteredResource(TestItemResource::class))->toBeFalse();
});
