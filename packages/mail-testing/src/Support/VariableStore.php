<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Facades\Storage;
use Moox\MailTesting\Enums\FillMode;

final class VariableStore
{
    public const PATH = 'mail-testing-variables.json';

    /**
     * @return array{recipient_mode: string, variables: list<array{token: string, mode: string, value: string}>}
     */
    public function read(): array
    {
        $disk = Storage::disk((string) config('mail-testing.disk', 'local'));

        if (! $disk->exists(self::PATH)) {
            return $this->defaults();
        }

        $decoded = json_decode((string) $disk->get(self::PATH), true);

        if (! is_array($decoded)) {
            return $this->defaults();
        }

        $recipient = FillMode::tryFrom((string) ($decoded['recipient_mode'] ?? '')) ?? FillMode::Random;

        return [
            'recipient_mode' => $recipient->value,
            'variables' => array_map(
                fn (VariableBinding $binding): array => $binding->toArray(),
                VariableBinding::collect($decoded['variables'] ?? []),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function write(array $state): void
    {
        $payload = $this->payloadFromState($state);

        Storage::disk((string) config('mail-testing.disk', 'local'))->put(
            self::PATH,
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array{recipient_mode: string, variables: list<array{token: string, mode: string, value: string}>}
     */
    public function payloadFromState(array $state): array
    {
        $recipient = FillMode::tryFrom((string) ($state['recipient_mode'] ?? '')) ?? FillMode::Random;

        return [
            'recipient_mode' => $recipient->value,
            'variables' => array_map(
                fn (VariableBinding $binding): array => $binding->toArray(),
                VariableBinding::collect($state['variables'] ?? []),
            ),
        ];
    }

    /**
     * @return array{recipient_mode: string, variables: list<array{token: string, mode: string, value: string}>}
     */
    private function defaults(): array
    {
        return [
            'recipient_mode' => FillMode::Random->value,
            'variables' => [],
        ];
    }
}
