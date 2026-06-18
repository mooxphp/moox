<?php

declare(strict_types=1);

use Moox\Audit\Support\ActivityEntryPresenter;

it('flattens nested attribute changes for display', function (): void {
    $result = ActivityEntryPresenter::flattenChanges([
        'old' => ['title' => 'Original', 'status' => 'draft'],
        'attributes' => ['title' => 'Updated', 'status' => 'draft'],
    ]);

    expect($result)->toBe([
        'title' => 'Original → Updated',
    ]);
});

it('formats property values as strings', function (): void {
    $result = ActivityEntryPresenter::flattenProperties([
        'source' => 'test',
        'flags' => ['a', 'b'],
        'enabled' => true,
    ]);

    expect($result['source'])->toBe('test')
        ->and($result['flags'])->toBe('["a","b"]')
        ->and($result['enabled'])->toBe('true');
});
