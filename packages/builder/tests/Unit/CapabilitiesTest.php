<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\Capabilities\MaxValue;
use Moox\Builder\FieldTypes\Capabilities\MinValue;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\PrefixSuffix;

it('applies max length capability to a component', function (): void {
    $field = new FieldDefinition(
        name: 'title',
        label: 'Title',
        type: 'text',
        config: ['maxLength' => 120],
    );

    $component = app(MaxLength::class)->apply(TextInput::make('title'), $field);

    expect($component->getMaxLength())->toBe(120)
        ->and(app(MaxLength::class)->rules($field))->toBe(['max:120']);
});

it('applies numeric min and max capabilities', function (): void {
    $field = new FieldDefinition(
        name: 'price',
        label: 'Price',
        type: 'number',
        config: ['min' => 1, 'max' => 99],
    );

    $component = TextInput::make('price')->numeric();
    $component = app(MinValue::class)->apply($component, $field);
    $component = app(MaxValue::class)->apply($component, $field);

    expect($component->getMinValue())->toBe(1)
        ->and($component->getMaxValue())->toBe(99)
        ->and(app(MinValue::class)->rules($field))->toBe(['min:1'])
        ->and(app(MaxValue::class)->rules($field))->toBe(['max:99']);
});

it('applies placeholder and prefix suffix capabilities', function (): void {
    $field = new FieldDefinition(
        name: 'amount',
        label: 'Amount',
        type: 'number',
        config: ['placeholder' => '0.00', 'prefix' => '€', 'suffix' => 'EUR'],
    );

    $component = TextInput::make('amount');
    $component = app(Placeholder::class)->apply($component, $field);
    $component = app(PrefixSuffix::class)->apply($component, $field);

    expect($component->getPlaceholder())->toBe('0.00')
        ->and($component->getPrefixLabel())->toBe('€')
        ->and($component->getSuffixLabel())->toBe('EUR');
});
