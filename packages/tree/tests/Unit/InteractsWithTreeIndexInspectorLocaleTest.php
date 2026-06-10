<?php

declare(strict_types=1);

use Moox\Tree\Filament\Concerns\InteractsWithTreeIndexInspectorLocale;
use Moox\Tree\Tests\TestCase;

uses(TestCase::class);

it('syncs passed inspector lang to the request query before mount', function (): void {
    $component = new class
    {
        use InteractsWithTreeIndexInspectorLocale;

        public ?string $lang = 'de_DE';

        public function sync(): void
        {
            $this->syncTreeInspectorLocaleToRequest();
        }
    };

    $component->sync();

    expect(request()->query('lang'))->toBe('de_DE')
        ->and(request()->input('lang'))->toBe('de_DE');
});

it('does not sync when inspector lang is empty', function (): void {
    $component = new class
    {
        use InteractsWithTreeIndexInspectorLocale;

        public ?string $lang = null;

        public function sync(): void
        {
            $this->syncTreeInspectorLocaleToRequest();
        }
    };

    $component->sync();

    expect(request()->query('lang'))->toBeNull()
        ->and(request()->input('lang'))->toBeNull();
});
