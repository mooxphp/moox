<?php

declare(strict_types=1);

namespace Moox\LoginLink\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginLinkRateLimiter
{
    public function __construct(
        protected ?Request $request = null,
    ) {
        $this->request ??= request();
    }

    public function tooManySendAttempts(
        ?string $email,
        string $process = RedemptionHandlerRegistry::DEFAULT_PROCESS,
        ?Model $subject = null,
    ): bool {
        if ($this->tooManyIpSendAttempts($process)) {
            return true;
        }

        if ($subject !== null && $this->tooManySubjectSendAttempts($process, $subject)) {
            return true;
        }

        if ($email === null || $email === '') {
            return false;
        }

        return RateLimiter::tooManyAttempts(
            $this->emailKey($email, $process),
            $this->sendMaxAttempts(),
        );
    }

    public function hitSendAttempt(
        ?string $email,
        string $process = RedemptionHandlerRegistry::DEFAULT_PROCESS,
        ?Model $subject = null,
    ): void {
        RateLimiter::hit($this->ipKey($process), $this->ipDecaySeconds());

        if ($subject !== null) {
            RateLimiter::hit($this->subjectKey($process, $subject), $this->sendDecaySeconds());
        }

        if ($email !== null && $email !== '') {
            RateLimiter::hit($this->emailKey($email, $process), $this->sendDecaySeconds());
        }
    }

    protected function tooManyIpSendAttempts(string $process): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->ipKey($process),
            $this->ipMaxAttempts(),
        );
    }

    protected function tooManySubjectSendAttempts(string $process, Model $subject): bool
    {
        return RateLimiter::tooManyAttempts(
            $this->subjectKey($process, $subject),
            $this->sendMaxAttempts(),
        );
    }

    protected function ipKey(string $process): string
    {
        return 'login-link:send:'.$process.':ip:'.($this->request->ip() ?? 'unknown');
    }

    protected function emailKey(string $email, string $process): string
    {
        return 'login-link:send:'.$process.':'.($this->request->ip() ?? 'unknown').'|'.mb_strtolower($email);
    }

    protected function subjectKey(string $process, Model $subject): string
    {
        return 'login-link:send:'.$process.':subject:'.$subject::class.':'.$subject->getKey().':'.($this->request->ip() ?? 'unknown');
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
