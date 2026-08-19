<?php

declare(strict_types=1);

namespace Moox\MailInbox;

use InvalidArgumentException;
use Moox\MailInbox\Contracts\InboxDriver;

/**
 * Resolves a mailbox name to its configured {@see InboxDriver}.
 *
 * Resolution path: mailbox config → driver name → registered driver factory → driver instance.
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
     * Register a driver factory under a name.
     *
     * @param  callable(array<string, mixed>): InboxDriver  $factory  Receives the resolved connection config.
     */
    public function register(string $driver, callable $factory): void
    {
        $this->driverFactories[$driver] = $factory;
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
            throw new InvalidArgumentException("Mailbox [{$name}] is not configured.");
        }

        $config = $this->mailboxes[$name];
        $driverName = $config['driver'];
        $connectionName = $config['connection'];

        if (! isset($this->driverFactories[$driverName])) {
            throw new InvalidArgumentException("Inbox driver [{$driverName}] has not been registered.");
        }

        if (! isset($this->connections[$connectionName])) {
            throw new InvalidArgumentException("Connection [{$connectionName}] is not configured.");
        }

        $this->resolved[$name] = ($this->driverFactories[$driverName])(
            array_merge($this->connections[$connectionName], ['mailbox_address' => $config['address'] ?? null])
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
