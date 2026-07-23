<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Support\ConditionalLogic;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

function conditionalField(array $settings = []): FieldDefinition
{
    return new FieldDefinition(
        name: 'company',
        label: 'Company',
        type: 'text',
        settings: $settings,
    );
}

function conditionalSettings(array $overrides = []): array
{
    return array_replace_recursive([
        'conditions' => [
            'enabled' => true,
            'action' => ConditionalLogic::ACTION_SHOW,
            'logic' => ConditionalLogic::LOGIC_AND,
            'rules' => [
                ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
            ],
        ],
    ], $overrides);
}

it('treats fields without conditions as always visible', function (): void {
    $field = conditionalField();

    expect(ConditionalLogic::isConfigured($field))->toBeFalse()
        ->and(ConditionalLogic::isVisibleForValues($field, []))->toBeTrue();
});

it('evaluates equals and not equals operators', function (): void {
    $field = conditionalField(conditionalSettings());

    expect(ConditionalLogic::isVisibleForValues($field, ['customer_type' => 'business']))->toBeTrue()
        ->and(ConditionalLogic::isVisibleForValues($field, ['customer_type' => 'private']))->toBeFalse();
});

it('evaluates empty and not empty operators', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'rules' => [
                ['field' => 'coupon', 'operator' => 'not_empty', 'value' => null],
            ],
        ],
    ]));

    expect(ConditionalLogic::isVisibleForValues($field, ['coupon' => 'SAVE10']))->toBeTrue()
        ->and(ConditionalLogic::isVisibleForValues($field, ['coupon' => '']))->toBeFalse()
        ->and(ConditionalLogic::isVisibleForValues($field, ['coupon' => null]))->toBeFalse();
});

it('evaluates contains for array values', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'rules' => [
                ['field' => 'tags', 'operator' => 'contains', 'value' => 'vip'],
            ],
        ],
    ]));

    expect(ConditionalLogic::isVisibleForValues($field, ['tags' => ['vip', 'news']]))->toBeTrue()
        ->and(ConditionalLogic::isVisibleForValues($field, ['tags' => ['news']]))->toBeFalse();
});

it('combines rules with and logic', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'logic' => ConditionalLogic::LOGIC_AND,
            'rules' => [
                ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                ['field' => 'country', 'operator' => 'equals', 'value' => 'de'],
            ],
        ],
    ]));

    expect(ConditionalLogic::isVisibleForValues($field, [
        'customer_type' => 'business',
        'country' => 'de',
    ]))->toBeTrue()
        ->and(ConditionalLogic::isVisibleForValues($field, [
            'customer_type' => 'business',
            'country' => 'at',
        ]))->toBeFalse();
});

it('combines rules with or logic', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'logic' => ConditionalLogic::LOGIC_OR,
            'rules' => [
                ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                ['field' => 'country', 'operator' => 'equals', 'value' => 'de'],
            ],
        ],
    ]));

    expect(ConditionalLogic::isVisibleForValues($field, [
        'customer_type' => 'private',
        'country' => 'de',
    ]))->toBeTrue();
});

it('inverts visibility for hide actions', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'action' => ConditionalLogic::ACTION_HIDE,
        ],
    ]));

    expect(ConditionalLogic::isVisibleForValues($field, ['customer_type' => 'business']))->toBeFalse()
        ->and(ConditionalLogic::isVisibleForValues($field, ['customer_type' => 'private']))->toBeTrue();
});

it('treats missing trigger values as unmatched rules', function (): void {
    $field = conditionalField(conditionalSettings());

    expect(ConditionalLogic::isVisibleForValues($field, []))->toBeFalse();
});

it('resolves trigger field names from configured rules', function (): void {
    $field = conditionalField(conditionalSettings([
        'conditions' => [
            'rules' => [
                ['field' => 'customer_type', 'operator' => 'equals', 'value' => 'business'],
                ['field' => 'country', 'operator' => 'equals', 'value' => 'de'],
            ],
        ],
    ]));

    expect($field->hasConditions())->toBeTrue()
        ->and($field->conditionTriggers())->toBe(['customer_type', 'country']);
});

it('evaluates passesForm using trigger field values from get', function (): void {
    $field = conditionalField(conditionalSettings());
    $get = fn (string $path): mixed => match ($path) {
        'customer_type' => 'business',
        default => null,
    };

    expect(ConditionalLogic::passesForm($field, $get))->toBeTrue();
});

it('normalizes condition settings for persistence', function (): void {
    expect(ConditionalLogic::normalizeSettings([
        'enabled' => 1,
        'action' => 'hide',
        'logic' => 'or',
        'rules' => [
            ['field' => 'type', 'operator' => 'invalid', 'value' => 'x'],
            ['field' => '', 'operator' => 'equals', 'value' => 'y'],
        ],
    ]))->toBe([
        'enabled' => true,
        'action' => ConditionalLogic::ACTION_HIDE,
        'logic' => ConditionalLogic::LOGIC_OR,
        'rules' => [
            ['field' => 'type', 'operator' => 'equals', 'value' => 'x'],
        ],
    ]);
});
