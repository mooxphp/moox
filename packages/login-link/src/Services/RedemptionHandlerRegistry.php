<?php

declare(strict_types=1);

namespace Moox\LoginLink\Services;

use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Handlers\LoginRedemptionHandler;

class RedemptionHandlerRegistry
{
    public const DEFAULT_PROCESS = 'login';

    /**
     * Build the handler map by merging package contributions (declared under
     * `{package}.login-link.handlers`, mirroring ScopeRegistry) and then applying
     * this package's own `login-link.handlers` (published app overrides last).
     *
     * @return array<string, class-string<RedemptionHandler>>
     */
    protected function handlers(): array
    {
        /** @var array<string, class-string<RedemptionHandler>> $merged */
        $merged = [];

        $packages = config('core.packages', []);

        foreach (array_keys($packages) as $packageKey) {
            if ($packageKey === 'login-link') {
                continue;
            }

            $contribution = config($packageKey.'.login-link.handlers', []);

            if (! is_array($contribution)) {
                continue;
            }

            /** @var array<string, class-string<RedemptionHandler>> $contribution */
            $merged = array_replace($merged, $contribution);
        }

        $own = config('login-link.handlers', [
            self::DEFAULT_PROCESS => LoginRedemptionHandler::class,
        ]);

        if (is_array($own)) {
            /** @var array<string, class-string<RedemptionHandler>> $own */
            $merged = array_replace($merged, $own);
        }

        return $merged;
    }

    /**
     * @return array<string, class-string<RedemptionHandler>>
     */
    public function all(): array
    {
        return $this->handlers();
    }

    public function has(string $process): bool
    {
        return array_key_exists($process, $this->handlers());
    }

    /**
     * @return class-string<RedemptionHandler>|null
     */
    public function resolve(string $process): ?string
    {
        return $this->handlers()[$process] ?? null;
    }

    public function get(string $process): ?RedemptionHandler
    {
        $class = $this->resolve($process);

        if ($class === null || ! class_exists($class)) {
            return null;
        }

        $handler = app($class);

        return $handler instanceof RedemptionHandler ? $handler : null;
    }
}
