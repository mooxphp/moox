<?php

declare(strict_types=1);

use Moox\Core\Support\Resources\SoftDeleteTab;

it('detects trash and deleted tabs', function (): void {
    expect(SoftDeleteTab::isTrashTab('deleted'))->toBeTrue()
        ->and(SoftDeleteTab::isTrashTab('trash'))->toBeTrue()
        ->and(SoftDeleteTab::isTrashTab('all'))->toBeFalse()
        ->and(SoftDeleteTab::isTrashTab(null))->toBeFalse()
        ->and(SoftDeleteTab::isTrashTab(''))->toBeFalse();
});

it('resolves tab from explicit value first', function (): void {
    expect(SoftDeleteTab::resolve(
        'deleted',
        fn (): string => 'all',
        ['tab' => 'trash'],
    ))->toBe('deleted');
});

it('resolves tab from current tab resolver before request query', function (): void {
    expect(SoftDeleteTab::resolve(
        null,
        fn (): string => 'deleted',
        ['tab' => 'all'],
    ))->toBe('deleted');
});

it('resolves tab from request query keys', function (): void {
    expect(SoftDeleteTab::resolve(null, null, ['tab' => 'trash']))->toBe('trash')
        ->and(SoftDeleteTab::resolve(null, null, ['activeTab' => 'deleted']))->toBe('deleted');
});

it('only includes trashed records on trash tabs', function (): void {
    expect(SoftDeleteTab::shouldIncludeTrashed('all'))->toBeFalse()
        ->and(SoftDeleteTab::shouldIncludeTrashed('deleted'))->toBeTrue()
        ->and(SoftDeleteTab::shouldIncludeTrashed(null, fn (): string => 'trash'))->toBeTrue()
        ->and(SoftDeleteTab::shouldIncludeTrashed(null, null, ['tab' => 'all']))->toBeFalse();
});
