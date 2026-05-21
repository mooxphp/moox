<?php

declare(strict_types=1);

use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

use Filament\Auth\Pages\Login as FilamentLogin;
use Moox\LoginLink\Concerns\InteractsWithLoginLinks;
use Moox\LoginLink\Support\PanelLoginEnhancer;

it('extends filament login with the login link trait', function (): void {
    $enhanced = PanelLoginEnhancer::resolve(FilamentLogin::class);

    expect($enhanced)->not->toBe(FilamentLogin::class)
        ->and(is_subclass_of($enhanced, FilamentLogin::class))->toBeTrue()
        ->and(in_array(InteractsWithLoginLinks::class, class_uses_recursive($enhanced), true))->toBeTrue();
});

it('does not re-enhance an already enhanced login class', function (): void {
    $first = PanelLoginEnhancer::resolve(FilamentLogin::class);
    $second = PanelLoginEnhancer::resolve($first);

    expect($second)->toBe($first);
});
