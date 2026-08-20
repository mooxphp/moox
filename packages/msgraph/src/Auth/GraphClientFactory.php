<?php

declare(strict_types=1);

namespace Moox\MsGraph\Auth;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Graph\Core\GraphClientFactory as SdkGraphClientFactory;
use Microsoft\Graph\Core\NationalCloud;
use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Kiota\Authentication\Oauth\ClientCredentialContext;
use Psr\Http\Message\RequestInterface;

/**
 * Builds authenticated GraphServiceClient instances for named connections.
 *
 * Every outgoing request carries Prefer: IdType="ImmutableId" so item IDs
 * remain stable across folder moves and mailbox operations.
 */
final class GraphClientFactory
{
    public function __construct(private ConnectionRegistry $registry)
    {
    }

    public function make(
        ?string $connection = null,
        ?HandlerStack $handlerStack = null,
    ): GraphServiceClient {
        $graphConnection = $this->registry->get($connection);

        $tokenContext = new ClientCredentialContext(
            $graphConnection->tenantId,
            $graphConnection->clientId,
            $graphConnection->clientSecret,
        );

        if ($handlerStack === null) {
            $handlerStack = SdkGraphClientFactory::getDefaultHandlerStack();
        }

        self::prependImmutableIdMiddleware($handlerStack);

        $httpClient = SdkGraphClientFactory::createWithMiddleware($handlerStack);

        $scopes = [];
        $nationalCloud = NationalCloud::GLOBAL;

        $authProvider = GraphPhpLeagueAuthenticationProvider::createWithAccessTokenProvider(
            new GraphPhpLeagueAccessTokenProvider($tokenContext, $scopes, $nationalCloud),
        );

        $requestAdapter = new GraphRequestAdapter($authProvider, $httpClient);

        return new GraphServiceClient($tokenContext, $scopes, $nationalCloud, $requestAdapter);
    }

    public static function prependImmutableIdMiddleware(HandlerStack $handlerStack): void
    {
        $handlerStack->unshift(
            Middleware::mapRequest(
                fn (RequestInterface $request): RequestInterface => $request->withHeader('Prefer', 'IdType="ImmutableId"'),
            ),
            'msgraph_prefer_immutable_id',
        );
    }
}
