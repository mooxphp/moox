<?php

declare(strict_types=1);

use Filament\Panel;
use Filament\PanelRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Tests\Support\TestUser;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $panel = Mockery::mock(Panel::class);
    $panel->shouldReceive('getAuthGuard')->andReturn('web');
    $panel->shouldReceive('getUrl')->andReturn('/admin');

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

    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
    ]);
    config()->set('core.packages', []);
});

it('redeems a valid login link once via the login handler', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'user@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'subject_type' => TestUser::class,
        'subject_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $service = app(LoginLinkRedemptionService::class);

    $result = $service->redeem($loginLink->getKey(), 'admin');

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($loginLink->fresh()->used_at)->not->toBeNull()
        ->and(Auth::guard('web')->id())->toBe($user->id);

    expect($service->redeem($loginLink->getKey(), 'admin'))->toBeNull();
});

it('reports why a login link cannot be redeemed', function (): void {
    $service = app(LoginLinkRedemptionService::class);

    expect($service->failureReason(999_999))->toBe('missing');

    $used = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
        'user_type' => TestUser::class,
        'user_id' => 1,
        'email' => 'used@example.com',
        'expires_at' => now()->addHour(),
        'used_at' => now(),
    ]);

    expect($service->failureReason($used->getKey()))->toBe('used');

    $expired = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
        'user_type' => TestUser::class,
        'user_id' => 1,
        'email' => 'expired@example.com',
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    expect($service->failureReason($expired->getKey()))->toBe('expired');
});

it('redeems legacy links that only have the user morph', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Legacy User',
        'email' => 'legacy@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(LoginLinkRedemptionService::class)->redeem($loginLink->getKey(), 'admin');

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and(Auth::guard('web')->id())->toBe($user->id);
});

it('rejects expired login links', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'expired@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => RedemptionHandlerRegistry::DEFAULT_PROCESS,
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'subject_type' => TestUser::class,
        'subject_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    expect(app(LoginLinkRedemptionService::class)->redeem($loginLink->getKey(), 'admin'))->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('fails closed when the process has no registered handler', function (): void {
    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'unknown@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => 'missing-process',
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'subject_type' => TestUser::class,
        'subject_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    expect(app(LoginLinkRedemptionService::class)->redeem($loginLink->getKey(), 'admin'))->toBeNull()
        ->and($loginLink->fresh()->used_at)->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
});

it('dispatches a custom registered handler for a process key', function (): void {
    $state = (object) ['handled' => false];

    $customHandler = new class($state) implements RedemptionHandler
    {
        public function __construct(private object $state)
        {
        }

        public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
        {
            $this->state->handled = true;

            return redirect('/custom-ok');
        }
    };

    $this->app->instance($customHandler::class, $customHandler);

    config()->set('login-link.handlers', [
        'login' => LoginRedemptionHandler::class,
        'custom' => $customHandler::class,
    ]);

    $user = TestUser::query()->create([
        'name' => 'Test User',
        'email' => 'custom@example.com',
        'password' => bcrypt('secret'),
    ]);

    $loginLink = LoginLink::query()->create([
        'panel_id' => 'admin',
        'process' => 'custom',
        'user_type' => TestUser::class,
        'user_id' => $user->id,
        'subject_type' => TestUser::class,
        'subject_id' => $user->id,
        'email' => $user->email,
        'expires_at' => now()->addHour(),
        'used_at' => null,
    ]);

    $result = app(LoginLinkRedemptionService::class)->redeem($loginLink->getKey(), 'admin');

    expect($result)->toBeInstanceOf(RedirectResponse::class)
        ->and($result->getTargetUrl())->toEndWith('/custom-ok')
        ->and($state->handled)->toBeTrue()
        ->and($loginLink->fresh()->used_at)->not->toBeNull()
        ->and(Auth::guard('web')->check())->toBeFalse();
});
