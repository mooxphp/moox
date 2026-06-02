<?php

declare(strict_types=1);

namespace Moox\Connect\Auth;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Contracts\AuthenticationStrategyInterface;
use Moox\Connect\Exceptions\ApiException;
use RuntimeException;

final class MultiAuthStrategy extends BaseAuthStrategy
{
    private array $strategies = [];

    private array $strategyMap = [];

    public function addStrategy(string $name, AuthenticationStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    public function mapEndpointToStrategy(string $endpoint, string $strategyName): void
    {
        if (! isset($this->strategies[$strategyName])) {
            throw new RuntimeException("Strategy '{$strategyName}' not found");
        }
        $this->strategyMap[$endpoint] = $strategyName;
    }

    public function authenticate(): void
    {
        if (empty($this->strategies)) {
            throw new ApiException(
                'No authentication strategies configured',
                400,
                null,
                null,
                ['auth_type' => 'multi']
            );
        }

        $errors = [];
        foreach ($this->strategies as $name => $strategy) {
            try {
                $strategy->authenticate();
            } catch (ApiException $e) {
                $errors[$name] = $e->getMessage();
            }
        }

        if (count($errors) === count($this->strategies)) {
            throw new ApiException(
                'All authentication strategies failed',
                401,
                null,
                null,
                ['auth_type' => 'multi', 'errors' => $errors]
            );
        }

        $this->authenticated = true;
        $this->credentials = array_map(
            fn ($strategy) => $strategy->getCredentials(),
            $this->strategies
        );
    }

    public function applyToRequest(ApiRequestInterface $request): ApiRequestInterface
    {
        if (! $this->isAuthenticated()) {
            throw new ApiException(
                'Authentication required before making requests',
                401,
                null,
                null,
                ['auth_type' => 'multi']
            );
        }

        $endpoint = $request->getEndpoint();
        $strategyName = $this->resolveStrategyForEndpoint($endpoint);

        if (! isset($this->strategies[$strategyName])) {
            throw new ApiException(
                "No strategy found for endpoint: {$endpoint}",
                400,
                $endpoint,
                null,
                ['auth_type' => 'multi']
            );
        }

        return $this->strategies[$strategyName]->applyToRequest($request);
    }

    public function refreshCredentials(): void
    {
        foreach ($this->strategies as $strategy) {
            try {
                $strategy->refreshCredentials();
            } catch (RuntimeException $e) {
                // Skip strategies that don't support refresh
                continue;
            }
        }
    }

    private function resolveStrategyForEndpoint(string $endpoint): string
    {
        // Try exact match first
        if (isset($this->strategyMap[$endpoint])) {
            return $this->strategyMap[$endpoint];
        }

        // Try pattern matching
        foreach ($this->strategyMap as $pattern => $strategyName) {
            if (str_starts_with($endpoint, $pattern)) {
                return $strategyName;
            }
        }

        // Fall back to first strategy if no mapping found
        return array_key_first($this->strategies);
    }

    public function getStrategy(string $name): ?AuthenticationStrategyInterface
    {
        return $this->strategies[$name] ?? null;
    }
}
