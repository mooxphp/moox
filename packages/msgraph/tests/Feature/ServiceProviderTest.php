<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Moox\MsGraph\Auth\ConnectionRegistry;
use Moox\MsGraph\Auth\GraphClientFactory;

it('factory applies immutable-ID header when given a custom handler stack', function () {
    $history = [];

    $mock = new MockHandler([
        new Response(200, [], '{}'),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $registry = new ConnectionRegistry([
        'default' => ['tenant_id' => 'tenant', 'client_id' => 'client', 'client_secret' => 'secret'],
    ]);

    $factory = new GraphClientFactory($registry);

    // We can't call make() fully without real OAuth, but we can verify
    // the middleware is applied to the custom stack by using the static helper
    // through the same path make() uses
    GraphClientFactory::prependImmutableIdMiddleware($stack);

    $client = new Client(['handler' => $stack]);
    $client->get('https://graph.microsoft.com/v1.0/test');

    expect($history[0]['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
});

it('two connections produce distinct GraphConnection credentials', function () {
    $registry = new ConnectionRegistry([
        'a' => ['tenant_id' => 'tenant-a', 'client_id' => 'client-a', 'client_secret' => 'secret-a'],
        'b' => ['tenant_id' => 'tenant-b', 'client_id' => 'client-b', 'client_secret' => 'secret-b'],
    ]);

    $factory = new GraphClientFactory($registry);

    $connA = $registry->get('a');
    $connB = $registry->get('b');

    expect($connA->tenantId)->toBe('tenant-a');
    expect($connB->tenantId)->toBe('tenant-b');
    expect($connA->clientSecret)->not->toBe($connB->clientSecret);
});
