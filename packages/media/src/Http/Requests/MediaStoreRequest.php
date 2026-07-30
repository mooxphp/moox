<?php

declare(strict_types=1);

namespace Moox\Media\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Moox\Media\Support\MediaUploadValidator;

class MediaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lang' => ['nullable', 'string', 'max:20'],
            'media_collection_id' => ['required', 'integer', 'min:1', 'exists:media_collections,id'],
            'file' => [
                'required',
                'file',
                'max:'.(int) config('media.upload.resource.max_file_size', 10240),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    try {
                        $apiAcceptedFileTypes = config('media.upload.api.accepted_file_types');

                        app(MediaUploadValidator::class)->ensureAccepted(
                            $value,
                            is_array($apiAcceptedFileTypes) ? $apiAcceptedFileTypes : null,
                        );
                    } catch (ValidationException $exception) {
                        $fail(collect($exception->errors())->flatten()->first() ?: $exception->getMessage());
                    }
                },
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function lang(): ?string
    {
        $value = $this->validated('lang');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public function mediaCollectionId(): int
    {
        return (int) $this->validated('media_collection_id');
    }

    public function name(): ?string
    {
        $value = $this->validated('name');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public function title(): ?string
    {
        $value = $this->validated('title');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    public function alt(): ?string
    {
        $value = $this->validated('alt');
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }
}
