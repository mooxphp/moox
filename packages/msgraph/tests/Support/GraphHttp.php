<?php

declare(strict_types=1);

namespace Moox\Msgraph\Tests\Support;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\Core\GraphClientFactory as SdkGraphClientFactory;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Microsoft\Kiota\Authentication\Oauth\ProviderFactory;
use Moox\Msgraph\Auth\GraphClientFactory;
use Moox\Msgraph\Mail\GraphInboxDriver;
use Moox\Msgraph\Mail\MailSettings;

final class GraphHttp
{
    public const MAILBOX = 'mailbox@example.com';

    public static function tokenResponse(): Response
    {
        return self::json(200, [
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'access_token' => 'test-token',
        ]);
    }

    /**
     * @param  array<string, string|array<int, string>>  $headers
     */
    public static function json(int $status, array|string $body, array $headers = []): Response
    {
        $payload = is_string($body) ? $body : json_encode($body, JSON_THROW_ON_ERROR);

        return new Response($status, array_merge(['Content-Type' => 'application/json'], $headers), $payload);
    }

    /**
     * @param  array<int, Response>  $responses
     */
    public static function mock(array $responses): MockHandler
    {
        return new MockHandler(array_merge([self::tokenResponse()], $responses));
    }

    /**
     * @param  array<int, mixed>  $history
     */
    public static function client(MockHandler $mock, array &$history): GraphServiceClient
    {
        $graphStack = HandlerStack::create($mock);
        $graphStack->push(Middleware::history($history));
        GraphClientFactory::prependImmutableIdMiddleware($graphStack);

        $oauthClient = new Client([
            'handler' => HandlerStack::create($mock),
            'http_errors' => true,
        ]);

        $tokenContext = new ClientCredentialContext('test-tenant', 'test-client', 'test-secret');
        $oauthProvider = ProviderFactory::create($tokenContext, ['httpClient' => $oauthClient]);

        $httpClient = SdkGraphClientFactory::createWithMiddleware($graphStack);

        $authProvider = GraphPhpLeagueAuthenticationProvider::createWithAccessTokenProvider(
            new GraphPhpLeagueAccessTokenProvider($tokenContext, [], NationalCloud::GLOBAL, null, $oauthProvider),
        );

        $adapter = new GraphRequestAdapter($authProvider, $httpClient);

        return new GraphServiceClient($tokenContext, [], NationalCloud::GLOBAL, $adapter);
    }

    /**
     * @param  array<int, mixed>  $history
     * @param  Closure(int): void|null  $sleeper
     */
    public static function driver(MockHandler $mock, array &$history, ?Closure $sleeper = null): GraphInboxDriver
    {
        return GraphInboxDriver::make(
            client: self::client($mock, $history),
            mailboxAddress: self::MAILBOX,
            settings: MailSettings::fromConfig(),
            sleeper: $sleeper,
        );
    }

    public static function folderCollection(string $id, string $displayName): Response
    {
        return self::json(200, [
            'value' => [
                [
                    'id' => $id,
                    'displayName' => $displayName,
                ],
            ],
        ]);
    }

    public static function emptyFolderCollection(): Response
    {
        return self::json(200, ['value' => []]);
    }

    public static function createdFolder(string $id, string $displayName): Response
    {
        return self::json(201, [
            'id' => $id,
            'displayName' => $displayName,
        ]);
    }

    public static function messageParent(string $parentFolderId): Response
    {
        return self::json(200, [
            'id' => 'msg-1',
            'parentFolderId' => $parentFolderId,
        ]);
    }

    public static function inboxFolder(string $id = 'folder-inbox'): Response
    {
        return self::json(200, [
            'id' => $id,
            'displayName' => 'Inbox',
        ]);
    }

    public static function movedMessage(): Response
    {
        return self::json(200, ['id' => 'msg-1']);
    }

    /**
     * @param  array<int, mixed>  $history
     */
    public static function graphRequests(array $history): array
    {
        return array_values(array_filter(
            $history,
            fn (array $entry): bool => str_contains((string) $entry['request']->getUri(), 'graph.microsoft.com'),
        ));
    }
}
