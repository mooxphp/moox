<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\Http\Requests\Concerns\SanitizesTemplatePayload;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;

class UpdateTemplateRequest extends FormRequest
{
    use SanitizesTemplatePayload;

    public function authorize(): bool
    {
        if (! ApiAuthorization::isEnabled()) {
            return true;
        }

        $template = $this->route('template');

        if (! $template instanceof Template) {
            return false;
        }

        return $this->user()?->can('update', $template) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $template = $this->route('template');
        $table = (new Template)->getTable();

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique($table, 'slug')->ignore($template?->getKey()),
            ],
            'content' => ['nullable', 'array'],
        ];
    }
}
