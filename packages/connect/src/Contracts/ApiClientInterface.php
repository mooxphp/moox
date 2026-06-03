<?php

declare(strict_types=1);

namespace Moox\Connect\Contracts;

interface ApiClientInterface
{
    public function sendRequest(ApiRequestInterface $request): ApiResponseInterface;

    public function authenticate(): void;

    public function refreshToken(): void; // Optional for OAuth or JWT APIs
}
