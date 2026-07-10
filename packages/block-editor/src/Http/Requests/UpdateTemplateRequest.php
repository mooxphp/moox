<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;
use Moox\BlockEditor\Support\TemplateContentSanitizer;

class UpdateTemplateRequest extends FormRequest
{
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

    protected function passedValidation(): void
    {
        /** @var TemplateContentSanitizer $sanitizer */
        $sanitizer = app(TemplateContentSanitizer::class);

        $content = $this->input('content');

        if (is_array($content)) {
            $this->merge([
                'content' => $sanitizer->sanitizeBlocks($content),
            ]);
        }
    }
}
