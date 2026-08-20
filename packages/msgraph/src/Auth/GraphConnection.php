<?php

declare(strict_types=1);

namespace Moox\MsGraph\Auth;

use Moox\MsGraph\Exceptions\InvalidConnectionException;

/**
 * Immutable value object holding Azure AD credentials for a single Graph connection.
 */
final readonly class GraphConnection
{
    public function __construct(
        public string $tenantId,
        public string $clientId,
        public string $clientSecret,
    ) {
        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            throw new InvalidConnectionException(
                'All credential fields (tenant_id, client_id, client_secret) must be non-empty.',
            );
        }
    }

    /**
     * @param  array{tenant_id?: string, client_id?: string, client_secret?: string}  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            tenantId: $config['tenant_id'] ?? '',
            clientId: $config['client_id'] ?? '',
            clientSecret: $config['client_secret'] ?? '',
        );
    }
}
