<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Closure;
use RuntimeException;

final class RetryHandler
{
    private int $maxAttempts;

    private int $delay;

    private string $strategy;

    public function __construct(
        int $maxAttempts = 3,
        int $delay = 1000,
        string $strategy = 'exponential'
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->delay = $delay;
        $this->strategy = $strategy;
    }

    public function execute(Closure $operation): mixed
    {
        $attempt = 1;
        $lastException = null;

        while ($attempt <= $this->maxAttempts) {
            try {
                return $operation();
            } catch (RuntimeException $e) {
                $lastException = $e;

                if ($attempt === $this->maxAttempts) {
                    break;
                }

                $this->wait($attempt);
                $attempt++;
            }
        }

        throw new RuntimeException(
            "Operation failed after {$this->maxAttempts} attempts: ".
            $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    private function wait(int $attempt): void
    {
        $delay = match ($this->strategy) {
            'fixed' => $this->delay,
            'linear' => $this->delay * $attempt,
            'exponential' => $this->delay * (2 ** ($attempt - 1)),
            default => throw new RuntimeException("Unknown retry strategy: {$this->strategy}")
        };

        usleep($delay * 1000); // Convert to microseconds
    }

    public function withMaxAttempts(int $maxAttempts): self
    {
        $clone = clone $this;
        $clone->maxAttempts = $maxAttempts;

        return $clone;
    }

    public function withDelay(int $delay): self
    {
        $clone = clone $this;
        $clone->delay = $delay;

        return $clone;
    }

    public function withStrategy(string $strategy): self
    {
        $clone = clone $this;
        $clone->strategy = $strategy;

        return $clone;
    }
}
