<?php

use Moox\Msgraph\Auth\GraphConnection;
use Moox\Msgraph\Exceptions\InvalidConnectionException;

it('constructs from valid credentials', function () {
    $conn = new GraphConnection('tenant', 'client', 'secret');

    expect($conn->tenantId)->toBe('tenant');
    expect($conn->clientId)->toBe('client');
    expect($conn->clientSecret)->toBe('secret');
});

it('rejects empty tenant_id', function () {
    new GraphConnection('', 'client', 'secret');
})->throws(InvalidConnectionException::class);

it('rejects empty client_id', function () {
    new GraphConnection('tenant', '', 'secret');
})->throws(InvalidConnectionException::class);

it('rejects empty client_secret', function () {
    new GraphConnection('tenant', 'client', '');
})->throws(InvalidConnectionException::class);

it('creates from array', function () {
    $conn = GraphConnection::fromArray([
        'tenant_id' => 't',
        'client_id' => 'c',
        'client_secret' => 's',
    ]);

    expect($conn->tenantId)->toBe('t');
});

it('fromArray rejects missing keys', function () {
    GraphConnection::fromArray(['tenant_id' => 't']);
})->throws(InvalidConnectionException::class);
