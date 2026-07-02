<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Operations;

use InvalidArgumentException;

final class InlineOperationRegistry
{
    /** @var array<int, InlineValueOperation> */
    private array $operations;

    /**
     * @param  array<int, string>|null  $operationClassNames
     */
    public function __construct(?array $operationClassNames = null)
    {
        $operationClassNames = $operationClassNames ?? config(
            'transform.inline_value_operations',
            [MapInlineValueOperation::class],
        );

        $this->operations = [];

        foreach ($operationClassNames as $class) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            $operation = app()->make($class);

            if (! $operation instanceof InlineValueOperation) {
                throw new InvalidArgumentException(sprintf(
                    'Inline operation [%s] must implement %s.',
                    $class,
                    InlineValueOperation::class,
                ));
            }

            $this->operations[] = $operation;
        }
    }

    public function isPayloadBaseExpression(string $operationSegment): bool
    {
        foreach ($this->operations as $operation) {
            if ($operation instanceof PayloadAwareInlineValueOperation && $operation->supports($operationSegment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function payloadBaseExpressionExists(array $payload, string $operationSegment): bool
    {
        foreach ($this->operations as $operation) {
            if (! $operation instanceof PayloadAwareInlineValueOperation || ! $operation->supports($operationSegment)) {
                continue;
            }

            return $operation->hasResolvablePaths($payload, $operationSegment);
        }

        return false;
    }

    /**
     * @param  array<int, string>  $warnings
     * @param  array<string, mixed>  $payload
     */
    public function applyOperation(
        string $operationSegment,
        mixed $value,
        string $destinationField,
        array &$warnings,
        array $payload = [],
    ): mixed {
        foreach ($this->operations as $operation) {
            if (! $operation->supports($operationSegment)) {
                continue;
            }

            if ($operation instanceof PayloadAwareInlineValueOperation && $payload !== []) {
                return $operation->applyWithPayload(
                    $operationSegment,
                    $value,
                    $destinationField,
                    $warnings,
                    $payload,
                );
            }

            return $operation->apply($value, $operationSegment, $destinationField, $warnings);
        }

        $warnings[] = "Unsupported mapping operation [{$operationSegment}] for destination field [{$destinationField}].";

        return $value;
    }
}
