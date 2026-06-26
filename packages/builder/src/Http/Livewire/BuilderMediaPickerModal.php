<?php

declare(strict_types=1);

namespace Moox\Builder\Http\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Models\Media;

class BuilderMediaPickerModal extends MediaPickerModal
{
    public string $modalId = 'mediaPickerModal';

    public string $statePath = '';

    public function toggleMediaSelection(int $mediaId): void
    {
        $media = $this->applyMediaScope(Media::query())
            ->where('id', $mediaId)
            ->first();

        if ($media instanceof Media && ! $this->isMimeAllowed((string) ($media->mime_type ?? ''))) {
            Notification::make()
                ->danger()
                ->title($this->mimeRestrictionNotification())
                ->send();

            return;
        }

        parent::toggleMediaSelection($mediaId);
    }

    public function applySelection(): void
    {
        /** @var Collection<int, Media> $selectedMedia */
        $selectedMedia = $this->applyMediaScope(Media::query())
            ->whereIn('id', $this->selectedMediaIds)
            ->get();

        $payload = [];

        if ($selectedMedia->isNotEmpty()) {
            $payload = $selectedMedia
                ->map(static fn (Media $media): array => [
                    'id' => $media->getKey(),
                    'url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'name' => $media->getAttribute('name'),
                ])
                ->values()
                ->all();
        }

        $this->dispatch('builder-media-selected', statePath: $this->statePath, media: $payload);
        $this->dispatch('close-modal', id: $this->modalId);
    }

    public function render(): View
    {
        /** @var View $view */
        $view = parent::render();

        return view('builder::livewire.builder-media-picker-modal', array_merge(
            $view->getData(),
            [
                'modalId' => $this->modalId,
                'excludedMimePrefixes' => $this->excludedMimePrefixes(),
                'onlyMimePrefixes' => $this->onlyMimePrefixes(),
            ],
        ));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyMediaScope(Builder $query): Builder
    {
        $query = parent::applyMediaScope($query);

        $onlyPrefixes = $this->onlyMimePrefixes();

        if ($onlyPrefixes !== []) {
            $query->where(function (Builder $scopedQuery) use ($onlyPrefixes): void {
                foreach ($onlyPrefixes as $prefix) {
                    $scopedQuery->orWhere('mime_type', 'like', $prefix.'%');
                }
            });
        }

        foreach ($this->excludedMimePrefixes() as $prefix) {
            $query->where('mime_type', 'not like', $prefix.'%');
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function onlyMimePrefixes(): array
    {
        return $this->normalizeMimePrefixes($this->uploadConfig['only_mime_prefixes'] ?? []);
    }

    /**
     * @return list<string>
     */
    protected function excludedMimePrefixes(): array
    {
        return $this->normalizeMimePrefixes($this->uploadConfig['excluded_mime_prefixes'] ?? []);
    }

    /**
     * @return list<string>
     */
    protected function normalizeMimePrefixes(mixed $prefixes): array
    {
        if (! is_array($prefixes)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $prefix): string => trim((string) $prefix), $prefixes),
            static fn (string $prefix): bool => $prefix !== '',
        ));
    }

    protected function isMimeAllowed(string $mimeType): bool
    {
        $mimeType = strtolower($mimeType);
        $onlyPrefixes = $this->onlyMimePrefixes();

        if ($onlyPrefixes !== []) {
            if ($mimeType === '') {
                return false;
            }

            foreach ($onlyPrefixes as $prefix) {
                if (str_starts_with($mimeType, strtolower($prefix))) {
                    return true;
                }
            }

            return false;
        }

        if ($mimeType === '') {
            return true;
        }

        foreach ($this->excludedMimePrefixes() as $prefix) {
            if (str_starts_with($mimeType, strtolower($prefix))) {
                return false;
            }
        }

        return true;
    }

    protected function mimeRestrictionNotification(): string
    {
        if ($this->onlyMimePrefixes() === ['image/']) {
            return __('builder::builder.validation.invalid_media_type', ['attribute' => __('builder::builder.field_types.image')]);
        }

        if (in_array('image/', $this->excludedMimePrefixes(), true)) {
            return __('builder::builder.validation.invalid_file_media_type', ['attribute' => __('builder::builder.field_types.file')]);
        }

        return __('builder::builder.validation.invalid_media');
    }
}
