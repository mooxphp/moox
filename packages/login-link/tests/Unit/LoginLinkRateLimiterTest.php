<?php

declare(strict_types=1);

use Moox\LoginLink\Tests\TestCase;

uses(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Moox\LoginLink\Services\LoginLinkRateLimiter;

beforeEach(function (): void {
    RateLimiter::clear('login-link:send:ip:203.0.113.9');
    RateLimiter::clear('login-link:send:203.0.113.9|user@example.com');
});

it('blocks send attempts after ip limit is reached', function (): void {
    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
    $limiter = new LoginLinkRateLimiter($request);

    for ($i = 0; $i < 5; $i++) {
        $limiter->hitSendAttempt('user@example.com');
    }

    expect($limiter->tooManySendAttempts('user@example.com'))->toBeTrue();
});

it('blocks send attempts per email before ip limit', function (): void {
    config()->set('login-link.rate_limit.send.ip_max_attempts', 100);

    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '203.0.113.9']);
    $limiter = new LoginLinkRateLimiter($request);

    for ($i = 0; $i < 3; $i++) {
        $limiter->hitSendAttempt('user@example.com');
    }

    expect($limiter->tooManySendAttempts('user@example.com'))->toBeTrue();
});
