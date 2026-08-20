<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Moox\MsGraph\Auth\GraphClientFactory;

it('prepends Prefer: IdType="ImmutableId" header to every request', function () {
    $history = [];

    $mock = new MockHandler([
        new Response(200, [], '{"value":[]}'),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    GraphClientFactory::prependImmutableIdMiddleware($stack);

    $client = new Client(['handler' => $stack]);
    $client->get('https://graph.microsoft.com/v1.0/me');

    expect($history)->toHaveCount(1);
    expect($history[0]['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
});

it('applies the header to multiple sequential requests', function () {
    $history = [];

    $mock = new MockHandler([
        new Response(200, [], '{}'),
        new Response(200, [], '{}'),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));
    GraphClientFactory::prependImmutableIdMiddleware($stack);

    $client = new Client(['handler' => $stack]);
    $client->get('https://graph.microsoft.com/v1.0/users');
    $client->get('https://graph.microsoft.com/v1.0/messages');

    expect($history[0]['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
    expect($history[1]['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
});
