<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
use Moox\Builder\FieldTypes\Capabilities\HelperText;
use Moox\Builder\FieldTypes\Capabilities\MaxLength;
use Moox\Builder\FieldTypes\Capabilities\MaxValue;
use Moox\Builder\FieldTypes\Capabilities\MinValue;
use Moox\Builder\FieldTypes\Capabilities\Placeholder;
use Moox\Builder\FieldTypes\Capabilities\PrefixSuffix;
use Moox\Builder\FieldTypes\Types\ColorFieldType;
use Moox\Builder\FieldTypes\Types\DateFieldType;
use Moox\Builder\FieldTypes\Types\DatetimeFieldType;
use Moox\Builder\FieldTypes\Types\EmailFieldType;
use Moox\Builder\FieldTypes\Types\TimeFieldType;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

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

it('includes helper text capability on color fields', function (): void {
    expect((new ColorFieldType)->capabilities())
        ->toContain(HelperText::class);
});

it('includes helper text capability on date fields', function (): void {
    expect((new DateFieldType)->capabilities())
        ->toContain(HelperText::class);
});

it('includes helper text capability on datetime fields', function (): void {
    expect((new DatetimeFieldType)->capabilities())
        ->toContain(HelperText::class);
});

it('includes helper text capability on email fields', function (): void {
    expect((new EmailFieldType)->capabilities())
        ->toContain(HelperText::class);
});

it('offers date-only display formats for date fields', function (): void {
    $select = app(DisplayFormat::class)->builderFieldsFor('date')[0];

    expect(array_keys($select->getOptions()))->toBe(['d.m.Y', 'd/m/Y', 'Y-m-d', 'm/d/Y']);
});

it('offers datetime display formats for datetime fields', function (): void {
    $select = app(DisplayFormat::class)->builderFieldsFor('datetime')[0];

    expect(array_keys($select->getOptions()))->toBe([
        'd.m.Y H:i',
        'd.m.Y H:i:s',
        'Y-m-d H:i',
        'Y-m-d H:i:s',
    ]);
});

it('offers time display formats for time fields', function (): void {
    $select = app(DisplayFormat::class)->builderFieldsFor('time')[0];

    expect(array_keys($select->getOptions()))->toBe(['H:i', 'H:i:s', 'g:i A']);
});

it('includes display format capability on time fields', function (): void {
    expect((new TimeFieldType)->capabilities())->toBe([
        DisplayFormat::class,
        DefaultValue::class,
    ]);
});

it('applies seconds on native runtime time components', function (): void {
    $field = new FieldDefinition(
        name: 'besichtigungszeit',
        label: 'Bevorzugte Besichtigungszeit',
        type: 'time',
        config: ['displayFormat' => 'H:i:s'],
    );

    $component = app(DisplayFormat::class)
        ->apply(TimePicker::make('besichtigungszeit')->native(true), $field);

    expect($component->isNative())->toBeTrue()
        ->and($component->hasSeconds())->toBeTrue();
});

it('omits custom display format styling on native runtime time components', function (): void {
    $field = new FieldDefinition(
        name: 'besichtigungszeit',
        label: 'Bevorzugte Besichtigungszeit',
        type: 'time',
        config: ['displayFormat' => 'g:i A'],
    );

    $component = app(DisplayFormat::class)
        ->apply(TimePicker::make('besichtigungszeit')->native(true), $field);

    expect($component->isNative())->toBeTrue()
        ->and($component->hasSeconds())->toBeFalse();
});
