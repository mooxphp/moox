<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('redirects to Filament login when enabled', function () {
    config()->set('moox-frontend-auth.enabled', true);
    config()->set('moox-frontend-auth.redirect_after_login', '/');

    $uri = '/__moox_frontend_auth_protected_enabled';

    Route::middleware(['web'])->get($uri, function () {
        return response('ok', 200);
    });

    $response = $this->get($uri);

    $response->assertRedirect(route('filament.admin.auth.login'));
    expect(session('url.intended'))->toBe('/');
});

it('does not redirect when disabled', function () {
    config()->set('moox-frontend-auth.enabled', false);

    $uri = '/__moox_frontend_auth_protected_disabled';

    Route::middleware(['web'])->get($uri, function () {
        return response('ok', 200);
    });

    $response = $this->get($uri);

    $response->assertOk();
    $response->assertSeeText('ok');
});

it('allows access when authenticated', function () {
    $user = User::factory()->create([
        'email' => 'tester@dev.de',
    ]);

    $uri = '/__moox_frontend_auth_protected_authenticated';

    Route::middleware(['web'])->get($uri, function () {
        return response('ok', 200);
    });

    $this->actingAs($user);

    $response = $this->get($uri);

    $response->assertOk();
    $response->assertSeeText('ok');
});

it('protects routes from routes/web.php when enabled', function () {
    config()->set('moox-frontend-auth.enabled', true);
    config()->set('moox-frontend-auth.redirect_after_login', '/');

    $response = $this->get(route('home'));

    $response->assertRedirect(route('filament.admin.auth.login'));
    expect(session('url.intended'))->toBe('/');
});

it('skips Livewire requests coming from Filament login referer', function () {
    config()->set('moox-frontend-auth.enabled', true);
    config()->set('moox-frontend-auth.redirect_after_login', '/');

    $referer = url(route('filament.admin.auth.login'));

    Route::post('/livewire/message', function () {
        return response('ok', 200);
    })->name('livewire.message');

    $response = $this->post('/livewire/message', [], [
        'X-Livewire' => 'true',
        'referer' => $referer,
    ]);

    $response->assertOk();
    $response->assertSeeText('ok');
});
