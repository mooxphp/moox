<?php

declare(strict_types=1);

namespace Moox\Msgraph\Auth;

use Moox\Msgraph\Exceptions\InvalidConnectionException;

/**
 * Resolves named connection credentials from config.
 */
final class ConnectionRegistry
{
    /**
     * @param  array<string, array{tenant_id?: string, client_id?: string, client_secret?: string}>  $connections
     */
    public function __construct(
        private array $connections,
        private string $defaultName = 'default',
    ) {}

    public function get(?string $name = null): GraphConnection
    {
        $name ??= $this->defaultName;

        if (! isset($this->connections[$name])) {
            throw new InvalidConnectionException("Graph connection [{$name}] is not configured.");
        }

        return GraphConnection::fromArray($this->connections[$name]);
    }
}
