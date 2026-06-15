<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

uses(Moox\Builder\Tests\TestCase::class);

use Moox\Builder\Exceptions\UnknownFieldTypeException;
use Moox\Builder\FieldTypes\Types\TextFieldType;
use Moox\Builder\Registry\FieldTypeRegistry;
use Moox\Builder\Tests\TestCase;

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

it('resolves all fifteen default field types', function (): void {
    $registry = app(FieldTypeRegistry::class);

    $keys = [
        'text', 'textarea', 'number', 'email', 'url', 'password',
        'select', 'multiselect', 'checkbox_list', 'radio', 'toggle',
        'date', 'datetime', 'time', 'color',
    ];

    foreach ($keys as $key) {
        expect($registry->get($key)::key())->toBe($key);
    }
});
