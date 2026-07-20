<?php

declare(strict_types=1);

require_once __DIR__.'/../TestCase.php';

use Moox\Builder\Resources\FieldGroupResource;
use Moox\Builder\Tests\TestCase;

uses(TestCase::class);

function fieldTypeSupportsFilter(?string $type): bool
{
    $method = new ReflectionMethod(FieldGroupResource::class, 'fieldTypeSupportsFilter');
    $method->setAccessible(true);

    return $method->invoke(null, $type);
}

it('offers the "show in table filter" toggle for every compiler-supported field type', function (string $type): void {
    expect(fieldTypeSupportsFilter($type))->toBeTrue();
})->with([
    'select', 'radio', 'button_group', 'toggle', 'relation',
    'text', 'textarea', 'email', 'url', 'rich_text',
]);

it('does not offer the toggle for field types with no filter support', function (string $type): void {
    expect(fieldTypeSupportsFilter($type))->toBeFalse();
})->with([
    'multiselect', 'checkbox_list', 'image', 'gallery', 'file',
    'group', 'clone', 'repeater', 'flexible_content', 'password',
    'number', 'range', 'date', 'datetime',
]);

it('does not offer the toggle when no type is selected yet', function (): void {
    expect(fieldTypeSupportsFilter(null))->toBeFalse();
});
