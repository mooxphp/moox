<?php

declare(strict_types=1);

use Filament\Panel;
use Filament\PanelRegistry;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Moox\LoginLink\Tests\Support\TestUser;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $panel = Mockery::mock(Panel::class);

    $registry = Mockery::mock(PanelRegistry::class);
    $registry->shouldReceive('get')->with('admin')->andReturn($panel);
    $this->app->instance(PanelRegistry::class, $registry);

    $this->app['db']->connection()->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
});

it('redeems a valid login link once', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $service = app(LoginLinkRedemptionService::class);

    $redeemed = $service->redeem($loginLink->getKey(), 'admin');

    expect($redeemed)->not->toBeNull()
        ->and($redeemed->is($user))->toBeTrue()
        ->and($loginLink->fresh()->used_at)->not->toBeNull();

    expect($service->redeem($loginLink->getKey(), 'admin'))->toBeNull();
});

it('rejects expired login links', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'expired@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    expect(app(LoginLinkRedemptionService::class)->redeem($loginLink->getKey(), 'admin'))->toBeNull();
});
