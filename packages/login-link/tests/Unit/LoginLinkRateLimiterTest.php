<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Moox\LoginLink\Services\LoginLinkRateLimiter;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;
use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    RateLimiter::clear('login-link:send:login:ip:203.0.113.9');
    RateLimiter::clear('login-link:send:login:203.0.113.9|user@example.com');
});

it('blocks send attempts after ip limit is reached', function (): void {
    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
    $limiter = new LoginLinkRateLimiter($request);
    $process = RedemptionHandlerRegistry::DEFAULT_PROCESS;

    for ($i = 0; $i < 5; $i++) {
        $limiter->hitSendAttempt('user@example.com', $process);
    }

    expect($limiter->tooManySendAttempts('user@example.com', $process))->toBeTrue();
});

it('blocks send attempts per email before ip limit', function (): void {
    config()->set('login-link.rate_limit.send.ip_max_attempts', 100);

    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
    $limiter = new LoginLinkRateLimiter($request);
    $process = RedemptionHandlerRegistry::DEFAULT_PROCESS;

    for ($i = 0; $i < 3; $i++) {
        $limiter->hitSendAttempt('user@example.com', $process);
    }

    expect($limiter->tooManySendAttempts('user@example.com', $process))->toBeTrue();
});

it('scopes rate limits per process', function (): void {
    config()->set('login-link.rate_limit.send.ip_max_attempts', 100);
    config()->set('login-link.rate_limit.send.max_attempts', 3);

    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
    $limiter = new LoginLinkRateLimiter($request);

    for ($i = 0; $i < 3; $i++) {
        $limiter->hitSendAttempt('user@example.com', 'login');
    }

    expect($limiter->tooManySendAttempts('user@example.com', 'login'))->toBeTrue()
        ->and($limiter->tooManySendAttempts('user@example.com', 'verify-address'))->toBeFalse();
});
