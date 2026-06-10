<?php

declare(strict_types=1);

namespace Moox\MailInbox\Support;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\Core\GraphClientFactory;
use Microsoft\Graph\Core\Middleware\Option\GraphTelemetryOption;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\GraphConstants;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Psr\Http\Message\RequestInterface;

/**
 * Builds the Microsoft Graph service client configured for inbound mail ingestion.
 *
 * Every Graph REST call must include Prefer: IdType="ImmutableId" so mailbox item IDs stay stable across folder moves;
 * omitting this header yields volatile/restorable IDs that have collided under our (scope, external_id) uniqueness constraint.
 */
final class MailInboxGraphServiceClientFactory
{
    /**
     * @param  array<int, string>  $scopes
     */
    public static function make(
        ClientCredentialContext $tokenContext,
        array $scopes = [],
        string $nationalCloud = NationalCloud::GLOBAL,
        ?Client $httpClient = null,
    ): GraphServiceClient {
        if ($httpClient === null) {
            GraphClientFactory::setNationalCloud($nationalCloud);
            GraphClientFactory::setTelemetryOption(
                new GraphTelemetryOption(GraphConstants::API_VERSION, GraphConstants::SDK_VERSION),
            );

            $handlerStack = GraphClientFactory::getDefaultHandlerStack();
            self::prependPreferImmutableIdHeader($handlerStack);

            $httpClient = GraphClientFactory::createWithMiddleware($handlerStack);
        }

        $authenticationProvider = GraphPhpLeagueAuthenticationProvider::createWithAccessTokenProvider(
            new GraphPhpLeagueAccessTokenProvider($tokenContext, $scopes, $nationalCloud),
        );

        $requestAdapter = new GraphRequestAdapter($authenticationProvider, $httpClient);

        return new GraphServiceClient($tokenContext, $scopes, $nationalCloud, $requestAdapter);
    }

    public static function prependPreferImmutableIdHeader(HandlerStack $handlerStack): void
    {
        $handlerStack->unshift(
            Middleware::mapRequest(
                fn (RequestInterface $request): RequestInterface => $request->withHeader('Prefer', 'IdType="ImmutableId"'),
            ),
            'mail_inbox_graph_prefer_immutable_id',
        );
    }
}
