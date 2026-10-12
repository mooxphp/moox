<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Moox\Contact\Models\Contact;
use Moox\MailTesting\Enums\FillMode;

final class PayloadResolver
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, string>
     */
    public function forRun(array $options, string $fingerprint, int $position): array
    {
        $recipient = FillMode::tryFrom((string) ($options['recipient_mode'] ?? FillMode::Random->value))
            ?? FillMode::Random;

        return $this->resolve(
            $recipient,
            $fingerprint,
            $position,
            VariableBinding::collect($options['variables'] ?? []),
        );
    }

    /**
     * @param  list<VariableBinding>  $bindings
     * @return array<string, string>
     */
    public function resolve(FillMode $recipient, string $fingerprint, int $position, array $bindings): array
    {
        $this->seed($fingerprint, $position);

        $payload = MailTestingPayload::fromContact($this->contact($recipient));

        foreach ($bindings as $binding) {
            $payload[$binding->token] = $this->value($binding, $position);
        }

        return $payload;
    }

    private function contact(FillMode $recipient): Contact
    {
        if ($recipient === FillMode::Demo) {
            return new Contact([
                'salutation_code' => 'mr',
                'academic_title' => null,
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'display_name' => 'Max Mustermann',
            ]);
        }

        return Contact::factory()->make();
    }

    private function value(VariableBinding $binding, int $position): string
    {
        if ($binding->mode === FillMode::Demo) {
            return $binding->value;
        }

        return match ($binding->token) {
            'invoiceNumber' => sprintf('RE-2026-%05d', $position),
            default => $this->patternValue($binding->value),
        };
    }

    private function patternValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return (string) fake()->word();
        }

        return fake()->bothify($value);
    }

    private function seed(string $fingerprint, int $position): void
    {
        fake()->seed((int) sprintf('%u', crc32($fingerprint.'|'.$position)));
    }
}
