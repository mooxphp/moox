<?php

declare(strict_types=1);

namespace Moox\Mjml;

class MjmlResult
{
    /**
     * @param  array<string, mixed>  $rawResult
     */
    public function __construct(
        protected array $rawResult,
    ) {}

    public function html(): string
    {
        return is_string($this->rawResult['html'] ?? null) ? $this->rawResult['html'] : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return is_array($this->rawResult['json'] ?? null) ? $this->rawResult['json'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->rawResult;
    }

    /**
     * @return list<MjmlError>
     */
    public function errors(): array
    {
        $errors = $this->rawResult['errors'] ?? [];

        if (! is_array($errors)) {
            return [];
        }

        return array_values(array_map(
            function (mixed $error): MjmlError {
                if (is_array($error)) {
                    return new MjmlError($error);
                }

                return new MjmlError(['message' => (string) $error]);
            },
            $errors,
        ));
    }

    public function hasErrors(): bool
    {
        return $this->errors() !== [];
    }
}
