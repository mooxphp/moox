<?php

declare(strict_types=1);

namespace Moox\Connect\Exceptions;

use RuntimeException;

final class TransformerException extends RuntimeException
{
    private string $transformerName;

    public static function invalidType(string $transformerName, string $expected, string $actual): self
    {
        $instance = new self("Expected type {$expected}, got {$actual} in transformer: {$transformerName}");
        $instance->transformerName = $transformerName;

        return $instance;
    }

    public static function transformationFailed(string $transformerName, string $reason): self
    {
        $instance = new self("Transformation failed in {$transformerName}: {$reason}");
        $instance->transformerName = $transformerName;

        return $instance;
    }

    public static function invalidOption(string $transformerName, string $option): self
    {
        $instance = new self("Invalid option '{$option}' for transformer: {$transformerName}");
        $instance->transformerName = $transformerName;

        return $instance;
    }

    public static function missingOption(string $transformerName, string $option): self
    {
        $instance = new self("Missing required option '{$option}' for transformer: {$transformerName}");
        $instance->transformerName = $transformerName;

        return $instance;
    }

    public function getTransformerName(): string
    {
        return $this->transformerName;
    }
}
