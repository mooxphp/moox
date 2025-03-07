<?php

declare(strict_types=1);

namespace Moox\Connect\Clients;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Contracts\ApiResponseInterface;
use Moox\Connect\Exceptions\ApiException;
use Moox\Connect\Http\RequestBuilder;
use RuntimeException;

final class GraphQLApiClient extends BaseApiClient
{
    private string $endpoint;

    public function __construct(
        string $endpoint,
        ...$args
    ) {
        parent::__construct(...$args);
        $this->endpoint = $endpoint;
    }

    protected function executeRequest(ApiRequestInterface $request): ApiResponseInterface
    {
        $query = $request->getBody()['query'] ?? null;
        $variables = $request->getBody()['variables'] ?? null;

        if (! is_string($query)) {
            throw new RuntimeException('GraphQL query must be a string');
        }

        $graphqlRequest = RequestBuilder::create()
            ->post()
            ->endpoint($this->endpoint)
            ->headers($request->getHeaders())
            ->json([
                'query' => $query,
                'variables' => $variables,
            ])
            ->build();

        $response = parent::sendRequest($graphqlRequest);
        $data = $response->json();

        if (isset($data['errors'])) {
            throw new ApiException(
                'GraphQL query failed',
                400,
                $this->endpoint,
                $response,
                ['graphql_errors' => $data['errors']]
            );
        }

        return $response;
    }

    public function query(string $query, ?array $variables = null): array
    {
        $request = RequestBuilder::create()
            ->post()
            ->endpoint($this->endpoint)
            ->body([
                'query' => $query,
                'variables' => $variables,
            ])
            ->build();

        $response = $this->executeRequest($request);

        return $response->json()['data'] ?? [];
    }

    public function mutation(string $mutation, ?array $variables = null): array
    {
        return $this->query($mutation, $variables);
    }

    public function batchQueries(array $queries): array
    {
        $batchQuery = [];
        foreach ($queries as $alias => $query) {
            if (is_numeric($alias)) {
                $alias = 'query'.$alias;
            }
            $batchQuery[] = "$alias: $query";
        }

        $combinedQuery = "query {\n".implode("\n", $batchQuery)."\n}";

        return $this->query($combinedQuery);
    }

    protected function shouldRefreshToken(RuntimeException $e): bool
    {
        if (parent::shouldRefreshToken($e)) {
            return true;
        }

        // Check for common GraphQL authentication errors
        $message = strtolower($e->getMessage());

        return str_contains($message, 'not authenticated') ||
               str_contains($message, 'authentication required') ||
               str_contains($message, 'invalid token');
    }
}
