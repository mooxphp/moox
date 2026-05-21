<?php

namespace Moox\LoginLink\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginLinkRateLimiter
{
    public function __construct(
        protected ?Request $request = null,
    ) {
        $this->request ??= request();
    }

    public function tooManySendAttempts(?string $email): bool
    {
        if ($this->tooManyIpSendAttempts()) {
            return true;
        }

        if ($email === null || $email === '') {
            return false;
        }

        return RateLimiter::tooManyAttempts(
            $this->emailKey($email),
            $this->sendMaxAttempts(),
        );
    }

    public function hitSendAttempt(?string $email): void
    {
        RateLimiter::hit($this->ipKey(), $this->ipDecaySeconds());

        if ($email !== null && $email !== '') {
            RateLimiter::hit($this->emailKey($email), $this->sendDecaySeconds());
        }
    }

    protected function tooManyIpSendAttempts(): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->ipKey(),
            $this->ipMaxAttempts(),
        );
    }

    protected function ipKey(): string
    {
        return 'login-link:send:ip:'.($this->request->ip() ?? 'unknown');
    }

    protected function emailKey(string $email): string
    {
        return 'login-link:send:'.($this->request->ip() ?? 'unknown').'|'.mb_strtolower($email);
    }

    protected function sendMaxAttempts(): int
    {
        return max(1, (int) config('login-link.rate_limit.send.max_attempts', 5));
    }

    protected function sendDecaySeconds(): int
    {
        return max(1, (int) config('login-link.rate_limit.send.decay_seconds', 60));
    }

    protected function ipMaxAttempts(): int
    {
        return max(1, (int) config('login-link.rate_limit.send.ip_max_attempts', 20));
    }

    protected function ipDecaySeconds(): int
    {
        return max(1, (int) config('login-link.rate_limit.send.ip_decay_seconds', 60));
    }
}
