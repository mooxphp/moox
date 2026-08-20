<?php

declare(strict_types=1);

namespace Moox\MailInbox;

use InvalidArgumentException;
use Moox\MailInbox\Contracts\InboxDriver;

/**
 * Resolves a mailbox name to its configured {@see InboxDriver}.
 *
 * Resolution path: mailbox config → driver name → registered driver factory → driver instance.
 *
 * Sync-state rows are never backfilled into mailboxes configuration: this package
 * cannot invent a driver name without knowing which adapter packages are installed.
 */
class InboxDriverManager
{
    /** @var array<string, callable(array<string, mixed>): InboxDriver> */
    private array $driverFactories = [];

    /** @var array<string, InboxDriver> */
    private array $resolved = [];

    /**
     * @param  array<string, array{driver: string, connection: string, address?: string|null}>  $mailboxes
     * @param  array<string, array<string, mixed>>  $connections
     */
    public function __construct(
        private readonly array $mailboxes,
        private readonly array $connections,
    ) {}

    /**
     * Actionable message when a scope has no mailboxes configuration entry.
     */
    public static function unconfiguredMailboxMessage(string $scope): string
    {
        return "Mailbox scope [{$scope}] is not configured. "
            ."Add mail-inbox.mailboxes.{$scope} in config/mail-inbox.php "
            .'with driver (name of a registered inbox driver), connection, and address.';
    }

    public function isMailboxConfigured(string $scope): bool
    {
        return isset($this->mailboxes[$scope]);
    }

    /**
     * Scopes present in sync state but missing from mailboxes configuration.
     *
     * We deliberately do not backfill mailboxes entries from sync-state rows:
     * this package cannot invent a driver name without knowing which adapter
     * packages are installed.
     *
     * @param  iterable<string>  $syncStateScopes
     * @return list<string>
     */
    public function unconfiguredScopes(iterable $syncStateScopes): array
    {
        $missing = [];

        foreach ($syncStateScopes as $scope) {
            if (! is_string($scope) || $scope === '') {
                continue;
            }

            if (! $this->isMailboxConfigured($scope)) {
                $missing[] = $scope;
            }
        }

        sort($missing);

        return $missing;
    }

    /**
     * Register a driver factory under a name.
     *
     * @param  callable(array<string, mixed>): InboxDriver  $factory  Receives the resolved connection config.
     */
    public function register(string $driver, callable $factory): void
    {
        $this->driverFactories[$driver] = $factory;
    }

    /**
     * Resolve the configured driver name for a mailbox.
     *
     * @throws InvalidArgumentException when the mailbox or its driver is not configured.
     */
    public function driverNameFor(string $mailbox): string
    {
        if (! isset($this->mailboxes[$mailbox])) {
            throw new InvalidArgumentException(self::unconfiguredMailboxMessage($mailbox));
        }

        $driverName = $this->mailboxes[$mailbox]['driver'] ?? null;

        if (! is_string($driverName) || $driverName === '') {
            throw new InvalidArgumentException(
                "Mailbox [{$mailbox}] has no driver configured. Set mail-inbox.mailboxes.{$mailbox}.driver in config."
            );
        }

        return $driverName;
    }

    /**
     * Resolve the driver for a named mailbox.
     */
    public function mailbox(string $name): InboxDriver
    {
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        if (! isset($this->mailboxes[$name])) {
            throw new InvalidArgumentException(self::unconfiguredMailboxMessage($name));
        }

        $config = $this->mailboxes[$name];
        $driverName = $this->driverNameFor($name);
        $connectionName = $config['connection'];

        if (! isset($this->driverFactories[$driverName])) {
            throw new InvalidArgumentException("Inbox driver [{$driverName}] has not been registered.");
        }

        if (! isset($this->connections[$connectionName])) {
            throw new InvalidArgumentException("Connection [{$connectionName}] is not configured.");
        }

        $this->resolved[$name] = ($this->driverFactories[$driverName])(
            array_merge($this->connections[$connectionName], [
                'connection' => $connectionName,
                'mailbox_address' => $config['address'] ?? null,
            ])
        );

        return $this->resolved[$name];
    }

    /**
     * Forget all resolved driver instances (useful in tests).
     */
    public function flush(): void
    {
        $this->resolved = [];
    }
}
