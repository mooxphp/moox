<?php

declare(strict_types=1);

namespace Moox\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MediaIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lang' => ['nullable', 'string', 'max:20'],
            'search' => ['nullable', 'string', 'max:200'],
            'collection' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'string', Rule::in(['image', 'video', 'document'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function lang(): ?string
    {
        $value = $this->validated('lang');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public function search(): ?string
    {
        $value = $this->validated('search');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public function collectionId(): ?int
    {
        $value = $this->validated('collection');

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' && ctype_digit($value) ? (int) $value : null;
        }

        return null;
    }

    public function type(): ?string
    {
        return $this->validated('type');
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 25);
    }
}
