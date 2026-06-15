<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

uses(TestCase::class);

use Moox\Builder\Services\FieldGroupPersistence;
use Moox\Builder\Tests\TestCase;

it('converts target entities to location rules and back', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->locationRulesFromEntities(['item', 'product']);

    expect($rules)->toHaveCount(2)
        ->and($rules[0][0])->toMatchArray(['param' => 'entity', 'operator' => '==', 'value' => 'item'])
        ->and($rules[1][0])->toMatchArray(['param' => 'entity', 'operator' => '==', 'value' => 'product'])
        ->and($persistence->entitiesFromLocationRules($rules))->toBe(['item', 'product']);
});

it('prefers target entities when resolving location rules', function (): void {
    $persistence = app(FieldGroupPersistence::class);

    $rules = $persistence->resolveLocationRules([
        'target_entities' => ['item'],
        'location_rules' => [
            ['param' => 'entity', 'operator' => '==', 'value' => 'ignored'],
        ],
    ]);

    expect($rules)->toHaveCount(1)
        ->and($rules[0][0]['value'])->toBe('item');
});
