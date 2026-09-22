<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Moox\MailTesting\Enums\FillMode;

final class VariableBinding
{
    public function __construct(
        public string $token,
        public FillMode $mode,
        public string $value,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): ?self
    {
        $token = self::normalizeToken($row['token'] ?? null);

        if ($token === '') {
            return null;
        }

        $mode = FillMode::tryFrom((string) ($row['mode'] ?? FillMode::Demo->value)) ?? FillMode::Demo;

        return new self($token, $mode, is_scalar($row['value'] ?? null) ? (string) $row['value'] : '');
    }

    /**
     * @return list<self>
     */
    public static function collect(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $bindings = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $binding = self::fromArray($row);

            if ($binding instanceof self) {
                $bindings[$binding->token] = $binding;
            }
        }

        ksort($bindings);

        return array_values($bindings);
    }

    public static function normalizeToken(mixed $token): string
    {
        if (! is_string($token)) {
            return '';
        }

        return trim($token, " \t\n\r\0\x0B{}");
    }

    /**
     * @return array{token: string, mode: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'mode' => $this->mode->value,
            'value' => $this->value,
        ];
    }
}
