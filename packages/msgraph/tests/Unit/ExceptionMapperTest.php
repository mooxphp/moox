<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Moox\Msgraph\Exceptions\ExceptionMapper;
use Moox\Msgraph\Exceptions\GraphAuthenticationException;
use Moox\Msgraph\Exceptions\GraphConnectionException;
use Moox\Msgraph\Exceptions\GraphException;
use Moox\Msgraph\Exceptions\GraphNotFoundException;
use Moox\Msgraph\Exceptions\GraphRateLimitException;

it('maps ConnectException to GraphConnectionException', function () {
    $original = new ConnectException('timeout', new Request('GET', '/'));

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphConnectionException::class);
    expect($mapped->getPrevious())->toBe($original);
});

it('maps 401 to GraphAuthenticationException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(401, [], 'Unauthorized'),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphAuthenticationException::class);
});

it('maps 403 to GraphAuthenticationException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(403, [], 'Forbidden'),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphAuthenticationException::class);
});

it('maps 404 to GraphNotFoundException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(404, [], 'Not Found'),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphNotFoundException::class);
});

it('maps 429 to GraphRateLimitException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(429, [], 'Too Many Requests'),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphRateLimitException::class);
});

it('maps 500 to generic GraphException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(500, [], 'Internal Server Error'),
    );

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphException::class);
    expect($mapped)->not->toBeInstanceOf(GraphAuthenticationException::class);
});

it('maps unknown throwable to GraphException', function () {
    $original = new RuntimeException('something broke');

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphException::class);
    expect($mapped->getPrevious())->toBe($original);
});
