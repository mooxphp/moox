<?php

use Moox\BlockEditor\Forms\Components\BlockEditor;

it('filters positive and negative block lists', function (): void {
    $field = BlockEditor::make('content')
        ->positiveBlock(['paragraph', '', 1, 'heading'])
        ->negativeBlock(['image', '', null, 'table']);

    expect($field->getPositiveBlock())->toBe(['paragraph', 'heading'])
        ->and($field->getNegativeBlock())->toBe(['image', 'table']);
});

it('resolves templates and json flags', function (): void {
    $field = BlockEditor::make('content')
        ->templates(false)
        ->templateSlug(' landing-page ')
        ->showJson();

    expect($field->getTemplatesEnabled())->toBeFalse()
        ->and($field->getTemplateSlug())->toBe('landing-page')
        ->and($field->getDeveloperJsonEnabled())->toBeTrue();
});

it('resolves add components flag', function (): void {
    $field = BlockEditor::make('content')
        ->addComponents(false);

    expect($field->getAddComponentsEnabled())->toBeFalse();
});

it('enables add components by default', function (): void {
    $field = BlockEditor::make('content');

    expect($field->getAddComponentsEnabled())->toBeTrue();
});

it('resolves json import flag', function (): void {
    $field = BlockEditor::make('content')
        ->showJsonImport();

    expect($field->getJsonImportEnabled())->toBeTrue();
});

it('disables json import by default', function (): void {
    $field = BlockEditor::make('content');

    expect($field->getJsonImportEnabled())->toBeFalse();
});

it('resolves media library endpoint and collection', function (): void {
    $field = BlockEditor::make('content')
        ->mediaLibraryApiUrl(' /api/media ')
        ->mediaLibraryCollection(' 1 ');

    expect($field->getMediaLibraryApiUrl())->toBe('/api/media')
        ->and($field->getMediaLibraryCollection())->toBe('1');
});

it('does not force a default media collection', function (): void {
    $field = BlockEditor::make('content');

    expect($field->getMediaLibraryCollection())->toBeNull();
});
