<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class ChoiceOptions
{
    /**
     * @param  array<string, bool|int|string|null>|null  $values
     */
    private function __construct(
        private readonly OptionsSource $source,
        private readonly ?array $values,
        private readonly ?string $reference,
    ) {
    }

    /**
     * @param  array<string, bool|int|string|null>  $values
     */
    public static function static(array $values): self
    {
        return new self(OptionsSource::Static, $values, null);
    }

    public static function enum(string $class): self
    {
        return new self(OptionsSource::Enum, null, $class);
    }

    public static function definition(string $name): self
    {
        return new self(OptionsSource::Definition, null, $name);
    }

    public static function provider(string $provider): self
    {
        return new self(OptionsSource::Provider, null, $provider);
    }

    public function source(): OptionsSource
    {
        return $this->source;
    }

    /**
     * @return array<string, bool|int|string|null>|null
     */
    public function values(): ?array
    {
        return $this->values;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }
}
