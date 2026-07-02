<?php

use Moox\BlockEditor\Livewire\BlockEditorField;

it('normalizes state and block type lists on mount', function (): void {
    $component = new BlockEditorField;
    $component->state = [
        ['id' => '1', 'type' => 'paragraph', 'content' => 'Text'],
    ];

    $component->mount(
        allowedBlockTypes: ['paragraph', '', 12, 'heading'],
        excludedBlockTypes: ['table', '', null, 'image'],
        themeTemplatesEnabled: false,
        developerJsonEnabled: true,
        jsonImportEnabled: true,
    );

    expect($component->state)->toBeString()
        ->and($component->state)->toContain('"type":"paragraph"')
        ->and($component->allowedBlockTypes)->toBe(['paragraph', 'heading'])
        ->and($component->excludedBlockTypes)->toBe(['table', 'image'])
        ->and($component->themeTemplatesEnabled)->toBeFalse()
        ->and($component->developerJsonEnabled)->toBeTrue()
        ->and($component->jsonImportEnabled)->toBeTrue();
});

it('uses empty json array for blank state', function (): void {
    $component = new BlockEditorField;
    $component->state = '';

    $component->mount();

    expect($component->state)->toBe('[]');
});
