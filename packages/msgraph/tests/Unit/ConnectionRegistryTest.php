<?php

use Moox\MsGraph\Auth\ConnectionRegistry;
use Moox\MsGraph\Auth\GraphConnection;
use Moox\MsGraph\Exceptions\InvalidConnectionException;

it('resolves a named connection as a GraphConnection value object', function () {
    $registry = new ConnectionRegistry([
        'primary' => [
            'tenant_id' => 't1',
            'client_id' => 'c1',
            'client_secret' => 's1',
        ],
    ], 'primary');

    $connection = $registry->get('primary');

    expect($connection)->toBeInstanceOf(GraphConnection::class);
    expect($connection->tenantId)->toBe('t1');
    expect($connection->clientId)->toBe('c1');
    expect($connection->clientSecret)->toBe('s1');
});

it('resolves the default connection when name is null', function () {
    $registry = new ConnectionRegistry([
        'main' => [
            'tenant_id' => 't',
            'client_id' => 'c',
            'client_secret' => 's',
        ],
    ], 'main');

    $a = $registry->get();
    $b = $registry->get('main');

    expect($a->tenantId)->toBe($b->tenantId);
});

it('throws for unknown connection name', function () {
    $registry = new ConnectionRegistry([], 'default');

    $registry->get('nonexistent');
})->throws(InvalidConnectionException::class, 'is not configured');

it('throws for incomplete credentials', function () {
    $registry = new ConnectionRegistry([
        'broken' => [
            'tenant_id' => '',
            'client_id' => 'c',
            'client_secret' => 's',
        ],
    ], 'broken');

    $registry->get('broken');
})->throws(InvalidConnectionException::class);

it('two connections yield independent GraphConnection objects', function () {
    $registry = new ConnectionRegistry([
        'a' => ['tenant_id' => 'ta', 'client_id' => 'ca', 'client_secret' => 'sa'],
        'b' => ['tenant_id' => 'tb', 'client_id' => 'cb', 'client_secret' => 'sb'],
    ]);

    $connA = $registry->get('a');
    $connB = $registry->get('b');

    expect($connA->tenantId)->toBe('ta');
    expect($connB->tenantId)->toBe('tb');
    expect($connA->clientId)->not->toBe($connB->clientId);
});
