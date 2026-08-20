<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Microsoft\Graph\Generated\Models\ODataErrors\MainError;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Moox\MsGraph\Exceptions\ExceptionMapper;
use Moox\MsGraph\Exceptions\GraphAuthenticationException;
use Moox\MsGraph\Exceptions\GraphConnectionException;
use Moox\MsGraph\Exceptions\GraphException;
use Moox\MsGraph\Exceptions\GraphItemNotFoundException;
use Moox\MsGraph\Exceptions\GraphMailboxNotFoundException;
use Moox\MsGraph\Exceptions\GraphNotFoundException;
use Moox\MsGraph\Exceptions\GraphRateLimitException;
use Moox\MsGraph\Exceptions\GraphSyncStateNotFoundException;

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

it('maps a generic 404 to GraphMailboxNotFoundException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(404, [], 'Not Found'),
    );

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphMailboxNotFoundException::class)
        ->and($mapped)->toBeInstanceOf(GraphNotFoundException::class);
});

it('maps 404 ErrorItemNotFound to GraphItemNotFoundException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(404, [], json_encode([
            'error' => ['code' => 'ErrorItemNotFound', 'message' => 'gone'],
        ])),
    );

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphItemNotFoundException::class)
        ->and($mapped)->toBeInstanceOf(GraphNotFoundException::class);
});

it('maps ApiException ODataError ErrorItemNotFound to GraphItemNotFoundException', function () {
    $error = new ODataError;
    $error->setResponseStatusCode(404);
    $main = new MainError;
    $main->setCode('ErrorItemNotFound');
    $main->setMessage('item gone');
    $error->setError($main);

    $mapped = ExceptionMapper::map($error);

    expect($mapped)->toBeInstanceOf(GraphItemNotFoundException::class);
});

it('maps ApiException ODataError mailbox 404 to GraphMailboxNotFoundException', function () {
    $error = new ODataError;
    $error->setResponseStatusCode(404);
    $main = new MainError;
    $main->setCode('MailboxNotEnabledForRESTAPI');
    $main->setMessage('no mailbox');
    $error->setError($main);

    $mapped = ExceptionMapper::map($error);

    expect($mapped)->toBeInstanceOf(GraphMailboxNotFoundException::class);
});

it('maps 429 to GraphRateLimitException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(429, [], 'Too Many Requests'),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphRateLimitException::class);
});

it('puts Retry-After seconds on GraphRateLimitException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(429, ['Retry-After' => '12'], 'Too Many Requests'),
    );

    $mapped = ExceptionMapper::map($original);

    expect($mapped)->toBeInstanceOf(GraphRateLimitException::class)
        ->and($mapped->retryAfterSeconds)->toBe(12);
});

it('maps 410 syncStateNotFound to GraphSyncStateNotFoundException', function () {
    $original = RequestException::create(
        new Request('GET', '/'),
        new Response(410, [], json_encode([
            'error' => ['code' => 'syncStateNotFound', 'message' => 'gone'],
        ])),
    );

    expect(ExceptionMapper::map($original))->toBeInstanceOf(GraphSyncStateNotFoundException::class);
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
