<?php

declare(strict_types=1);

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Carbon;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Capabilities\DefaultValue;
use Moox\Builder\FieldTypes\Capabilities\DisplayFormat;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('builds a toggle for toggle field default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('toggle');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(Toggle::class);
});

it('builds a select for option field default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('select');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(Select::class)
        ->and($fields[0]->isLive())->toBeTrue();
});

it('builds a multi select for multiselect and checkbox list default values in the admin', function (): void {
    $capability = app(DefaultValue::class);

    $multiselect = $capability->builderFieldsFor('multiselect')[0];
    $checkboxList = $capability->builderFieldsFor('checkbox_list')[0];

    expect($multiselect)->toBeInstanceOf(Select::class)
        ->and($checkboxList)->toBeInstanceOf(Select::class)
        ->and($multiselect->isMultiple())->toBeTrue()
        ->and($checkboxList->isMultiple())->toBeTrue();
});

it('resolves legacy string defaults for toggle fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'active',
        label: 'Active',
        type: 'toggle',
        config: ['default' => 'true'],
    );

    $component = Toggle::make('active');
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBeTrue();
});

it('applies boolean defaults for toggle fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'active',
        label: 'Active',
        type: 'toggle',
        config: ['default' => true],
    );

    $component = Toggle::make('active');
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBeTrue();
});

it('applies numeric defaults for number fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'price',
        label: 'Price',
        type: 'number',
        config: ['default' => '42.5'],
    );

    $component = TextInput::make('price');
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBe(42.5);
});

it('applies array defaults for multiselect fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'fuel',
        label: 'Fuel',
        type: 'multiselect',
        config: ['default' => ['petrol', 'diesel']],
    );

    $component = Select::make('fuel')->multiple();
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBe(['petrol', 'diesel']);
});

it('applies option defaults for button group fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'getriebe',
        label: 'Getriebe',
        type: 'button_group',
        config: ['default' => 'manual'],
        options: [
            ['label' => 'Schaltgetriebe', 'value' => 'manual'],
            ['label' => 'Automatik', 'value' => 'automatic'],
        ],
    );

    $component = ToggleButtons::make('getriebe')
        ->options(['manual' => 'Schaltgetriebe', 'automatic' => 'Automatik']);

    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBe('manual')
        ->and($capability->resolveForField($field))->toBe('manual');
});

it('normalizes color defaults to hex values', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'lackfarbe',
        label: 'Lackfarbe',
        type: 'color',
        config: ['default' => 'ff0000'],
    );

    expect($capability->resolveForField($field))->toBe('#ff0000')
        ->and($capability->normalizeColorValue('ff0000'))->toBe('#ff0000')
        ->and($capability->normalizeColorValue('#336699'))->toBe('#336699');
});

it('builds a live color picker for color field default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('color');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(ColorPicker::class)
        ->and($fields[0]->isLive())->toBeTrue();
});

it('applies color defaults on runtime components', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'lackfarbe',
        label: 'Lackfarbe',
        type: 'color',
        config: ['default' => '#1a1a1a'],
    );

    $component = ColorPicker::make('lackfarbe');
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBe('#1a1a1a');
});

it('applies array defaults for checkbox list fields at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'extras',
        label: 'Extras',
        type: 'checkbox_list',
        config: ['default' => ['nav', 'heated_seats']],
    );

    $component = CheckboxList::make('extras');
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBe(['nav', 'heated_seats']);
});

it('builds an email input for email field default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('email');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(TextInput::class)
        ->and($fields[0]->getType())->toBe('text');
});

it('builds a url-validated input for url and oembed field default values in the admin', function (): void {
    $capability = app(DefaultValue::class);

    $url = $capability->builderFieldsFor('url')[0];
    $oembed = $capability->builderFieldsFor('oembed')[0];

    expect($url)->toBeInstanceOf(TextInput::class)
        ->and($oembed)->toBeInstanceOf(TextInput::class);
});

it('ignores invalid url defaults at runtime for url and oembed fields', function (): void {
    $capability = app(DefaultValue::class);

    $urlField = new FieldDefinition(
        name: 'hersteller-seite',
        label: 'Herstellerseite',
        type: 'url',
        config: ['default' => 'keine-url'],
    );

    $oembedField = new FieldDefinition(
        name: 'fahrzeugvideo',
        label: 'Fahrzeugvideo',
        type: 'oembed',
        config: ['default' => 'youtube'],
    );

    expect($capability->resolveForField($urlField))->toBeNull()
        ->and($capability->resolveForField($oembedField))->toBeNull();
});

it('resolves valid url defaults at runtime for url and oembed fields', function (): void {
    $capability = app(DefaultValue::class);

    $url = 'https://www.youtube.com/watch?v=demo';

    expect($capability->resolveForField(new FieldDefinition(
        name: 'hersteller-seite',
        label: 'Herstellerseite',
        type: 'url',
        config: ['default' => $url],
    )))->toBe($url)
        ->and($capability->resolveForField(new FieldDefinition(
            name: 'fahrzeugvideo',
            label: 'Fahrzeugvideo',
            type: 'oembed',
            config: ['default' => $url],
        )))->toBe($url);
});

it('ignores text defaults that exceed the configured max length at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'plz',
        label: 'PLZ',
        type: 'text',
        config: ['default' => '123456', 'maxLength' => 5],
    );

    expect($capability->resolveForField($field))->toBeNull()
        ->and($capability->resolveForField(new FieldDefinition(
            name: 'plz',
            label: 'PLZ',
            type: 'text',
            config: ['default' => '12345', 'maxLength' => 5],
        )))->toBe('12345');
});

it('builds max-length-aware default inputs for text fields in the admin', function (): void {
    $text = app(DefaultValue::class)->builderFieldsFor('text')[0];
    $textarea = app(DefaultValue::class)->builderFieldsFor('textarea')[0];
    $email = app(DefaultValue::class)->builderFieldsFor('email')[0];

    expect($text)->toBeInstanceOf(TextInput::class)
        ->and($textarea)->toBeInstanceOf(Textarea::class)
        ->and($email)->toBeInstanceOf(TextInput::class);
});

it('builds a numeric input for number field default values in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('number');

    expect($fields)->toHaveCount(1)
        ->and($fields[0])->toBeInstanceOf(TextInput::class)
        ->and($fields[0]->isNumeric())->toBeTrue();
});

it('ignores number defaults outside configured min and max at runtime', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'price',
        label: 'Price',
        type: 'number',
        config: ['default' => 150, 'min' => 0, 'max' => 100],
    );

    expect($capability->resolveForField($field))->toBeNull()
        ->and($capability->resolveForField(new FieldDefinition(
            name: 'price',
            label: 'Price',
            type: 'number',
            config: ['default' => 50, 'min' => 0, 'max' => 100],
        )))->toBe(50);
});

it('builds temporal default controls for date fields in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('date');

    expect($fields)->toHaveCount(2)
        ->and($fields[0])->toBeInstanceOf(Toggle::class)
        ->and($fields[1])->toBeInstanceOf(DatePicker::class);
});

it('builds temporal default controls for datetime fields in the admin', function (): void {
    $fields = app(DefaultValue::class)->builderFieldsFor('datetime');

    expect($fields)->toHaveCount(2)
        ->and($fields[0])->toBeInstanceOf(Toggle::class)
        ->and($fields[1])->toBeInstanceOf(DateTimePicker::class);
});

it('resolves date defaults to carbon instances', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'erstzulassung',
        label: 'Erstzulassung',
        type: 'date',
        config: ['default' => '2022-06-15', 'displayFormat' => 'd.m.Y'],
    );

    $resolved = $capability->resolveForField($field);

    expect($resolved)->toBeInstanceOf(Carbon::class)
        ->and($resolved->toDateString())->toBe('2022-06-15');
});

it('resolves current date when default now is enabled', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'erstzulassung',
        label: 'Erstzulassung',
        type: 'date',
        config: ['defaultNow' => true],
    );

    $resolved = $capability->resolveForField($field);

    expect($resolved)->toBeInstanceOf(Carbon::class)
        ->and($resolved->toDateString())->toBe(now()->toDateString());
});

it('resolves datetime defaults to carbon instances', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'letzte-wartung',
        label: 'Letzte Wartung',
        type: 'datetime',
        config: ['default' => '2024-03-01 09:30:00', 'displayFormat' => 'd.m.Y H:i'],
    );

    $resolved = $capability->resolveForField($field);

    expect($resolved)->toBeInstanceOf(Carbon::class)
        ->and($resolved->format('Y-m-d H:i'))->toBe('2024-03-01 09:30');
});

it('resolves current datetime when default now is enabled', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'letzte-wartung',
        label: 'Letzte Wartung',
        type: 'datetime',
        config: ['defaultNow' => true],
    );

    $resolved = $capability->resolveForField($field);

    expect($resolved)->toBeInstanceOf(Carbon::class)
        ->and($resolved->format('Y-m-d H:i'))->toBe(now()->format('Y-m-d H:i'));
});

it('applies date defaults on runtime components', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'erstzulassung',
        label: 'Erstzulassung',
        type: 'date',
        config: ['default' => '2022-06-15'],
    );

    $component = DatePicker::make('erstzulassung')->native(false);
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBeInstanceOf(Carbon::class)
        ->and($result->getDefaultState()->toDateString())->toBe('2022-06-15');
});

it('applies datetime defaults on runtime components', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'letzte-wartung',
        label: 'Letzte Wartung',
        type: 'datetime',
        config: ['default' => '2024-03-01 09:30:00'],
    );

    $component = DateTimePicker::make('letzte-wartung')->native(false);
    $result = $capability->apply($component, $field);

    expect($result->getDefaultState())->toBeInstanceOf(Carbon::class)
        ->and($result->getDefaultState()->format('Y-m-d H:i'))->toBe('2024-03-01 09:30');
});

it('applies display and storage formats on runtime datetime components', function (): void {
    $field = new FieldDefinition(
        name: 'letzte-wartung',
        label: 'Letzte Wartung',
        type: 'datetime',
        config: ['displayFormat' => 'd.m.Y H:i:s'],
    );

    $component = app(DisplayFormat::class)
        ->apply(DateTimePicker::make('letzte-wartung')->native(false), $field);

    expect($component->getDisplayFormat())->toBe('d.m.Y H:i:s')
        ->and($component->getFormat())->toBe('Y-m-d H:i:s');
});

it('collects option choices from field option rows', function (): void {
    $capability = app(DefaultValue::class);

    $choices = (new ReflectionClass($capability))
        ->getMethod('optionChoices')
        ->invoke($capability, [
            ['label' => 'Benzin', 'value' => 'petrol'],
            ['label' => 'Diesel', 'value' => 'diesel'],
            ['label' => '', 'value' => ''],
        ]);

    expect($choices)->toBe([
        'petrol' => 'Benzin',
        'diesel' => 'Diesel',
    ]);
});

it('merges defaults into flexible content block data', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'content',
        label: 'Content',
        type: 'flexible_content',
        children: collect([
            new FieldDefinition(
                name: 'hero',
                label: 'Hero',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(
                        name: 'title',
                        label: 'Title',
                        type: 'text',
                        config: ['default' => 'Default title'],
                    ),
                    new FieldDefinition(
                        name: 'subtitle',
                        label: 'Subtitle',
                        type: 'text',
                        config: ['default' => 'Default subtitle'],
                    ),
                ]),
            ),
        ]),
    );

    $merged = $capability->mergeCompoundDefaults($field, [
        ['type' => 'hero', 'data' => ['title' => 'Saved title']],
        ['type' => 'hero', 'data' => ['title' => '', 'subtitle' => '']],
    ]);

    expect($merged)->toBe([
        ['type' => 'hero', 'data' => ['title' => 'Saved title', 'subtitle' => 'Default subtitle']],
        ['type' => 'hero', 'data' => ['title' => 'Default title', 'subtitle' => 'Default subtitle']],
    ]);
});

it('merges defaults into repeater rows', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'items',
        label: 'Items',
        type: 'repeater',
        children: collect([
            new FieldDefinition(
                name: 'label',
                label: 'Label',
                type: 'text',
                config: ['default' => 'Item'],
            ),
        ]),
    );

    $merged = $capability->mergeCompoundDefaults($field, [
        ['label' => 'Custom'],
        ['label' => ''],
    ]);

    expect($merged)->toBe([
        ['label' => 'Custom'],
        ['label' => 'Item'],
    ]);
});

it('builds default data for flexible layout children', function (): void {
    $capability = app(DefaultValue::class);

    $children = collect([
        new FieldDefinition(
            name: 'layout-checkbox',
            label: 'Layout checkbox',
            type: 'checkbox_list',
            config: ['default' => ['option-a']],
            options: [
                ['label' => 'Checkbox layout', 'value' => 'option-a'],
                ['label' => 'Checkbox layout 2', 'value' => 'option-b'],
            ],
        ),
        new FieldDefinition(
            name: 'flexible-1',
            label: 'Flexible 1',
            type: 'button_group',
            config: ['default' => 'button-flexible-1'],
            options: [
                ['label' => 'Button flexible 1', 'value' => 'button-flexible-1'],
                ['label' => 'Button flexible 2', 'value' => 'button-flexible-2'],
            ],
        ),
        new FieldDefinition(
            name: 'lackfarbe',
            label: 'Lackfarbe',
            type: 'color',
            config: ['default' => '1a1a1a'],
        ),
    ]);

    expect($capability->defaultDataForChildren($children))->toBe([
        'layout-checkbox' => ['option-a'],
        'flexible-1' => 'button-flexible-1',
        'lackfarbe' => '#1a1a1a',
    ]);
});

it('merges color defaults into group rows', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'details',
        label: 'Details',
        type: 'group',
        children: collect([
            new FieldDefinition(
                name: 'lackfarbe',
                label: 'Lackfarbe',
                type: 'color',
                config: ['default' => '#336699'],
            ),
        ]),
    );

    $merged = $capability->mergeCompoundDefaults($field, [
        ['lackfarbe' => ''],
    ]);

    expect($merged)->toBe([
        ['lackfarbe' => '#336699'],
    ]);
});

it('merges color defaults into flexible content block data', function (): void {
    $capability = app(DefaultValue::class);

    $field = new FieldDefinition(
        name: 'content',
        label: 'Content',
        type: 'flexible_content',
        children: collect([
            new FieldDefinition(
                name: 'hero',
                label: 'Hero',
                type: 'flexible_layout',
                children: collect([
                    new FieldDefinition(
                        name: 'lackfarbe',
                        label: 'Lackfarbe',
                        type: 'color',
                        config: ['default' => 'ff0000'],
                    ),
                ]),
            ),
        ]),
    );

    $merged = $capability->mergeCompoundDefaults($field, [
        ['type' => 'hero', 'data' => []],
    ]);

    expect($merged)->toBe([
        ['type' => 'hero', 'data' => ['lackfarbe' => '#ff0000']],
    ]);
});
