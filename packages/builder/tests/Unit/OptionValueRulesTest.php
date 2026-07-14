<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\Support\OptionValueRules;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('rejects invalid scalar option values', function (): void {
    $field = new FieldDefinition(
        name: 'status',
        label: 'Status',
        type: 'select',
        options: [
            ['label' => 'A', 'value' => '0'],
            ['label' => 'B', 'value' => 'kein value'],
        ],
    );

    OptionValueRules::assertValid($field, '0');

    expect(fn () => OptionValueRules::assertValid($field, 'invalid'))
        ->toThrow(InvalidArgumentException::class);
});

it('rejects invalid array option values', function (): void {
    $field = new FieldDefinition(
        name: 'tags',
        label: 'Tags',
        type: 'checkbox_list',
        options: [
            ['label' => 'A', 'value' => '0'],
            ['label' => 'B', 'value' => 'kein value'],
        ],
    );

    OptionValueRules::assertValid($field, ['0', 'kein value']);

    expect(fn () => OptionValueRules::assertValid($field, ['value']))
        ->toThrow(InvalidArgumentException::class);
});

it('builds validation rules for option fields', function (): void {
    $field = new FieldDefinition(
        name: 'tags',
        label: 'Tags',
        type: 'multiselect',
        options: [
            ['label' => 'A', 'value' => 'a'],
        ],
    );

    expect(OptionValueRules::forField($field))->toHaveCount(2);
});
