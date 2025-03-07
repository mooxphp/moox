<?php

declare(strict_types=1);

namespace Moox\Connect\Contracts;

interface TransformerInterface
{
    public function transform(mixed $value, array $options = []): mixed;

    public function reverseTransform(mixed $value, array $options = []): mixed;

    public function supports(mixed $value, array $options = []): bool;

    public function getName(): string;

    public function validateOptions(array $options): void;
}
