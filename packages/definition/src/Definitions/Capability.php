<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class Capability
{
    /**
     * @param  list<CapabilityParameter>  $input
     * @param  list<string>  $errors
     */
    public function __construct(
        private readonly string $name,
        private readonly array $input,
        private readonly ?string $output,
        private readonly array $errors,
        private readonly ?string $authorization,
        private readonly bool $sideEffects,
    ) {
    }

    /**
     * @param  array<string, string>  $input
     * @param  list<string>  $errors
     */
    public static function named(
        string $name,
        array $input = [],
        ?string $output = null,
        array $errors = [],
        ?string $authorization = null,
        bool $sideEffects = false,
    ): self {
        $parameters = [];

        foreach ($input as $parameter => $type) {
            $parameters[] = CapabilityParameter::fromType($parameter, $type);
        }

        return new self($name, $parameters, $output, $errors, $authorization, $sideEffects);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<CapabilityParameter>
     */
    public function input(): array
    {
        return $this->input;
    }

    public function output(): ?string
    {
        return $this->output;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function authorization(): ?string
    {
        return $this->authorization;
    }

    public function sideEffects(): bool
    {
        return $this->sideEffects;
    }
}
