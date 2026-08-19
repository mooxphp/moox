<?php

declare(strict_types=1);

namespace Moox\Invoice\Exceptions;

use InvalidArgumentException;

class RejectedDraftFieldException extends InvalidArgumentException
{
    public function __construct(
        private readonly string $field,
        /** @var class-string */
        private readonly string $modelClass,
    ) {
        parent::__construct(self::formatMessage($field, $modelClass));
    }

    /**
     * @param  class-string  $modelClass
     */
    public static function forField(string $field, string $modelClass): self
    {
        return new self($field, $modelClass);
    }

    public function field(): string
    {
        return $this->field;
    }

    /**
     * @return class-string
     */
    public function modelClass(): string
    {
        return $this->modelClass;
    }

    private static function formatMessage(string $field, string $modelClass): string
    {
        return sprintf(
            'Draft field "%s" is not accepted by model %s.',
            $field,
            $modelClass,
        );
    }
}
