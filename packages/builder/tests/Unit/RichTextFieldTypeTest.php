<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';
require_once __DIR__.'/../Support/TestItem.php';
require_once __DIR__.'/../Support/TestItemResource.php';

use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Moox\Builder\Data\FieldDefinition;
use Moox\Builder\FieldTypes\Types\RichTextFieldType;
use Moox\Builder\Models\Field;
use Moox\Builder\Models\FieldGroup;
use Moox\Builder\Models\FieldValue;
use Moox\Builder\Registry\DefinitionRegistry;
use Moox\Builder\Services\CustomFieldsManager;
use Moox\Builder\Services\FieldValueValidator;
use Moox\Builder\Support\RichTextValue;
use Moox\Builder\Tests\Support\TestItem;
use Moox\Builder\Tests\Support\TestItemResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $this->createItemsTable();
});

it('treats empty rich text html as empty', function (): void {
    expect(RichTextValue::isEmpty(null))->toBeTrue()
        ->and(RichTextValue::isEmpty(''))->toBeTrue()
        ->and(RichTextValue::isEmpty('<p></p>'))->toBeTrue()
        ->and(RichTextValue::isEmpty('<p><br></p>'))->toBeTrue()
        ->and(RichTextValue::isEmpty('<p>&nbsp;</p>'))->toBeTrue()
        ->and(RichTextValue::isEmpty('<p>Hello</p>'))->toBeFalse();
});

it('rejects required rich text fields that only contain empty editor html', function (): void {
    $field = new FieldDefinition(
        name: 'description',
        label: 'Description',
        type: 'rich_text',
        validation: ['required' => true, 'rules' => []],
    );

    expect(fn () => app(FieldValueValidator::class)->assertValid($field, '<p></p>'))
        ->toThrow(ValidationException::class);
});

it('accepts required rich text fields with visible content', function (): void {
    $field = new FieldDefinition(
        name: 'description',
        label: 'Description',
        type: 'rich_text',
        validation: ['required' => true, 'rules' => []],
    );

    app(FieldValueValidator::class)->assertValid($field, '<p>Hello world</p>');

    expect(true)->toBeTrue();
});

it('adds a custom required rule to rich text components instead of filament required', function (): void {
    $field = new FieldDefinition(
        name: 'description',
        label: 'Description',
        type: 'rich_text',
        validation: ['required' => true, 'rules' => []],
    );

    $component = (new RichTextFieldType)->formComponent($field);

    expect($component->isRequired())->toBeFalse();
});

it('does not persist empty required rich text values on save', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Content',
        'slug' => 'content-rich-text',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'description',
        'label' => 'Description',
        'type' => 'rich_text',
        'sort' => 0,
        'validation' => ['required' => true, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    expect(fn () => app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['description' => '<p><br></p>'],
    ))->toThrow(ValidationException::class);

    expect(FieldValue::query()->forRecord('item', $record->getKey())->count())->toBe(0);
});

it('strips executable xss vectors from rich text while keeping formatting', function (): void {
    $dirty = '<p class="intro" style="color:red">Hello <strong>world</strong></p>'
        .'<script>alert(1)</script>'
        .'<img src="x" onerror="alert(2)">'
        .'<a href="javascript:alert(3)">click</a>'
        .'<a href="https://example.com">safe</a>';

    $clean = RichTextValue::sanitizeForPersist($dirty);

    expect($clean)
        ->toBeString()
        ->toContain('<strong>world</strong>')
        ->toContain('class="intro"')
        ->toContain('href="https://example.com"')
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('javascript:');
});

it('sanitizes tip tap json documents when persisting through the field type', function (): void {
    $document = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'click',
                        'marks' => [
                            [
                                'type' => 'link',
                                'attrs' => ['href' => 'javascript:alert(1)', 'target' => null],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'safe',
                        'marks' => [
                            [
                                'type' => 'link',
                                'attrs' => ['href' => 'https://example.com', 'target' => null],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $persisted = (new RichTextFieldType)->persistValue($document);

    expect($persisted)
        ->toBeString()
        ->toContain('safe')
        ->toContain('href="https://example.com"')
        ->not->toContain('javascript:');
});

it('sanitizes rich text html when persisting through the field type', function (): void {
    $persisted = (new RichTextFieldType)->persistValue('<p>ok</p><script>alert(1)</script>');

    expect($persisted)
        ->toContain('<p>ok</p>')
        ->not->toContain('<script');
});

it('persists required rich text values with real content', function (): void {
    Cache::forget(DefinitionRegistry::CACHE_KEY);

    $group = FieldGroup::query()->create([
        'name' => 'Content',
        'slug' => 'content-rich-text-valid',
        'location_rules' => [[['param' => 'entity', 'operator' => '==', 'value' => 'item']]],
        'active' => true,
    ]);

    Field::query()->create([
        'field_group_id' => $group->getKey(),
        'name' => 'description',
        'label' => 'Description',
        'type' => 'rich_text',
        'sort' => 0,
        'validation' => ['required' => true, 'rules' => []],
    ]);

    $record = TestItem::query()->create(['title' => 'Demo']);

    app(CustomFieldsManager::class)->saveFromFormData(
        TestItemResource::class,
        $record,
        ['description' => '<p>Published</p>'],
    );

    $stored = FieldValue::query()
        ->forRecord('item', $record->getKey())
        ->where('field_name', 'description')
        ->first();

    expect($stored?->value_text)->toBe('<p>Published</p>');
});
