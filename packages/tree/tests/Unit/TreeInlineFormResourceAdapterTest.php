<?php

declare(strict_types=1);

use Moox\Tree\Filament\Concerns\ProvidesInlineResourceFormActions;
use Moox\Tree\Support\TreeInlineFormResourceAdapter;
use Moox\Tree\Tests\Support\TestForwardTreeResource;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class);

it('wraps a forwarded resource with inline form actions', function (): void {
    $adaptedClass = TreeInlineFormResourceAdapter::resolve(TestForwardTreeResource::class);

    expect($adaptedClass)->not->toBe(TestForwardTreeResource::class)
        ->and(is_subclass_of($adaptedClass, TestForwardTreeResource::class))->toBeTrue()
        ->and(in_array(ProvidesInlineResourceFormActions::class, class_uses($adaptedClass), true))->toBeTrue();

    $adaptedSaveAction = $adaptedClass::getSaveAction();

    expect($adaptedSaveAction->canSubmitForm())->toBeTrue()
        ->and($adaptedSaveAction->getFormToSubmit())->toBe('form');
});

it('returns the same adapted class on repeated resolve', function (): void {
    expect(TreeInlineFormResourceAdapter::resolve(TestForwardTreeResource::class))
        ->toBe(TreeInlineFormResourceAdapter::resolve(TestForwardTreeResource::class));
});
