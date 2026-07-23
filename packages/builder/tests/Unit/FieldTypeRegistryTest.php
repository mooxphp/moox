<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Exceptions\UnknownFieldTypeException;
use Moox\Builder\FieldTypes\Types\TextFieldType;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Support\MediaIntegration;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

it('registers and resolves field types', function (): void {
    $registry = new FieldTypeRegistry;
    $registry->register(new TextFieldType);

    expect($registry->get('text'))->toBeInstanceOf(TextFieldType::class)
        ->and($registry->all())->toHaveKey('text');
});

it('throws for unknown field type keys', function (): void {
    $registry = new FieldTypeRegistry;

    $registry->get('missing');
})->throws(UnknownFieldTypeException::class);

it('resolves all default field types', function (): void {
    $registry = app(FieldTypeRegistry::class);

    $keys = [
        'text', 'textarea', 'number', 'email', 'url', 'password',
        'select', 'multiselect', 'checkbox_list', 'radio', 'toggle',
        'date', 'datetime', 'time', 'color',
        'range', 'button_group', 'link',
    ];

    if (MediaIntegration::isAvailable()) {
        $keys[] = 'image';
        $keys[] = 'gallery';
        $keys[] = 'file';
    }

    $keys = array_merge($keys, [
        'rich_text',
        'message', 'oembed',
        'tab', 'group', 'clone', 'repeater', 'flexible_content', 'flexible_layout',
    ]);

    foreach ($keys as $key) {
        expect($registry->get($key)::key())->toBe($key);
    }
});

it('exposes compound field types for tab children but not nested tabs', function (): void {
    $registry = app(FieldTypeRegistry::class);

    $options = $registry->optionsForTabChildren();

    expect($options)->toHaveKeys(['repeater', 'group', 'clone', 'flexible_content', 'text'])
        ->and($options)->not->toHaveKey('tab')
        ->and($options)->not->toHaveKey('flexible_layout');
});

it('limits subfield options to leaf field types', function (): void {
    $registry = app(FieldTypeRegistry::class);

    $options = $registry->optionsForSubFields();

    expect($options)->toHaveKey('text')
        ->and($options)->not->toHaveKey('repeater')
        ->and($options)->not->toHaveKey('group')
        ->and($options)->not->toHaveKey('clone');
});
