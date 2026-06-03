<?php

declare(strict_types=1);

namespace Moox\Connect\Clients;

use Moox\Connect\Contracts\ApiRequestInterface;
use Moox\Connect\Contracts\ApiResponseInterface;
use Moox\Connect\Exceptions\ApiException;

final class RestApiClient extends BaseApiClient
{
    protected function executeRequest(ApiRequestInterface $request): ApiResponseInterface
    {
        $ch = curl_init();

        $options = [
            CURLOPT_URL => $request->getFullUrl(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $request->getMethod(),
            CURLOPT_HTTPHEADER => $this->formatHeaders($request->getHeaders()),
        ];

        if ($request->getBody() !== null) {
            $options[CURLOPT_POSTFIELDS] = $request->getBody();
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new ApiException(
                'HTTP request failed: '.$error,
                curl_errno($ch),
                $request->getEndpoint(),
                null,
                ['curl_error' => $error]
            );
        }

        $headers = $this->parseHeaders(substr($response, 0, $headerSize));
        $body = substr($response, $headerSize);

        return $this->responseParser->parse($body, $statusCode, $headers);
    }

    private function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = "{$name}: {$value}";
        }

        return $formatted;
    }

    private function parseHeaders(string $headerContent): array
    {
        $headers = [];
        $lines = preg_split('/\r\n|\n|\r/', $headerContent);

        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $name = trim(strtolower($parts[0]));
                $value = trim($parts[1]);
                $headers[$name] = $value;
            }
        }

        return $headers;
    }
}
