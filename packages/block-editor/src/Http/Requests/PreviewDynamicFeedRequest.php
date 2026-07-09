<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Moox\BlockEditor\EntityQuery\EntityQuerySourceRegistry;

class PreviewDynamicFeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $registeredSources = collect(EntityQuerySourceRegistry::sources())
            ->map(fn ($source) => $source->key())
            ->all();

        return [
            'sourceKey' => ['required', 'string', Rule::in($registeredSources)],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.(int) config('moox-editor.dynamic_feed.max_limit', 50)],
            'orderBy' => ['nullable', 'string'],
            'orderDirection' => ['nullable', Rule::in(['asc', 'desc'])],
            'view' => ['nullable', 'string'],
            'filters' => ['nullable', 'array'],
            'filters.category_id' => ['nullable', 'integer'],
        ];
    }
}
