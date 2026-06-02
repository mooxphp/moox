<?php

namespace Moox\Connect\Exceptions;

use Exception;
use Moox\Connect\Http\ApiResponse;

class ApiException extends Exception
{
    private int $statusCode;

    private ?string $endpoint;

    private ?ApiResponse $response;

    private array $context;

    public function __construct(
        string $message,
        int $statusCode,
        ?string $endpoint = null,
        ?ApiResponse $response = null,
        array $context = []
    ) {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->endpoint = $endpoint;
        $this->response = $response;
        $this->context = $context;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function getResponse(): ?ApiResponse
    {
        return $this->response;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function fromResponse(
        ApiResponse $response,
        ?string $endpoint = null,
        array $context = []
    ): self {
        return new self(
            $response->json()['message'] ?? 'API request failed',
            $response->getStatusCode(),
            $endpoint,
            $response,
            $context
        );
    }
}
