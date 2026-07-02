<?php

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;

class IndexTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ApiAuthorization::isEnabled()) {
            return true;
        }

        return $this->user()?->can('viewAny', Template::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:100'],
            'sort' => ['sometimes', 'string', Rule::in(['id', 'name', 'slug', 'created_at', 'updated_at'])],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
