<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\Models\Template;
use Moox\BlockEditor\Support\ApiAuthorization;
use Moox\BlockEditor\Support\TemplateContentSanitizer;

class StoreTemplateRequest extends FormRequest
{
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

    protected function passedValidation(): void
    {
        /** @var TemplateContentSanitizer $sanitizer */
        $sanitizer = app(TemplateContentSanitizer::class);

        $content = $this->input('content');

        $this->merge([
            'content' => is_array($content) ? $sanitizer->sanitizeBlocks($content) : null,
        ]);
    }
}
