<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\Http\Requests\Concerns\SanitizesTemplatePayload;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;

class StoreTemplateRequest extends FormRequest
{
    use SanitizesTemplatePayload;

    public function authorize(): bool
    {
        if (! ApiAuthorization::isEnabled()) {
            return true;
        }

        return $this->user()?->can('create', Template::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $table = (new Template)->getTable();

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique($table, 'slug')],
            'content' => ['nullable', 'array'],
        ];
    }
}
