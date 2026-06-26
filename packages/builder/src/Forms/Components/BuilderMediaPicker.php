<?php

declare(strict_types=1);

namespace Moox\Builder\Forms\Components;

use Closure;
use Illuminate\Support\Facades\Schema;
use Moox\Builder\Support\MediaFieldValueSupport;
use Moox\Media\Forms\Components\MediaPicker;
use Moox\Media\Models\Media;

class BuilderMediaPicker extends MediaPicker
{
    protected string $view = 'builder::forms.components.builder-media-picker';

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadStateFromRelationshipsUsing(static function (): void {
            // Hydration is handled by SchemaCompiler via builder field values.
        });

        $this->getUploadedFileUsing(static function (): ?array {
            return null;
        });

        $this->saveRelationshipsUsing(static function (): void {
            // Values are persisted by CustomFieldsManager, not model columns.
        });

        $this->dehydrated(true);
    }

    /**
     * @param  Closure|list<string>  $prefixes
     */
    public function excludeMimePrefixes(Closure|array $prefixes): static
    {
        $this->uploadConfig['excluded_mime_prefixes'] = $prefixes instanceof Closure ? $prefixes() : $prefixes;

        return $this;
    }

    /**
     * @param  Closure|list<string>  $prefixes
     */
    public function onlyMimePrefixes(Closure|array $prefixes): static
    {
        $this->uploadConfig['only_mime_prefixes'] = $prefixes instanceof Closure ? $prefixes() : $prefixes;

        return $this;
    }

    /**
     * @return list<array{id: int, url: string, file_name: string, name: string, mime_type: ?string}>
     */
    public function getInitialPreviewMedia(): array
    {
        if (! class_exists(Media::class)) {
            return [];
        }

        if (! Schema::hasTable('media')) {
            return [];
        }

        $ids = MediaFieldValueSupport::extractIds($this->getState());

        if ($ids === []) {
            return [];
        }

        return Media::query()
            ->whereIn('id', $ids)
            ->get()
            ->map(static function (Media $media): array {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'name' => $media->name ?? $media->file_name,
                    'mime_type' => $media->mime_type,
                ];
            })
            ->values()
            ->all();
    }
}
