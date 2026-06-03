<?php

declare(strict_types=1);

namespace Moox\Connect\DataObjects;

use Carbon\Carbon;
use Moox\Connect\Enums\ExchangeContext;
use Moox\Connect\Enums\ExchangeDirection;
use Moox\Connect\Enums\ExchangeTrigger;

final class ApiExchange
{
    public function __construct(
        private string $id,
        private string $apiConnectionId,
        private string $endpointId,
        private ExchangeDirection $direction,
        private ExchangeContext $context,
        private ExchangeTrigger $trigger,
        private ?string $jobId,
        private ?string $userId,
        private array $request,
        private array $response,
        private int $statusCode,
        private Carbon $timestamp,
        private array $metadata = []
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getApiConnectionId(): string
    {
        return $this->apiConnectionId;
    }

    public function getEndpointId(): string
    {
        return $this->endpointId;
    }

    public function getDirection(): ExchangeDirection
    {
        return $this->direction;
    }

    public function getContext(): ExchangeContext
    {
        return $this->context;
    }

    public function getTrigger(): ExchangeTrigger
    {
        return $this->trigger;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getRequest(): array
    {
        return $this->request;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTimestamp(): Carbon
    {
        return $this->timestamp;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'api_connection_id' => $this->apiConnectionId,
            'endpoint_id' => $this->endpointId,
            'direction' => $this->direction->value,
            'context' => $this->context->value,
            'trigger' => $this->trigger->value,
            'job_id' => $this->jobId,
            'user_id' => $this->userId,
            'request' => $this->request,
            'response' => $this->response,
            'status_code' => $this->statusCode,
            'timestamp' => $this->timestamp->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }
}
