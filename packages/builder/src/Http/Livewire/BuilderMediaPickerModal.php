<?php

declare(strict_types=1);

namespace Moox\Builder\Http\Livewire;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Moox\Media\Http\Livewire\MediaPickerModal;
use Moox\Media\Models\Media;

class BuilderMediaPickerModal extends MediaPickerModal
{
    public string $modalId = 'mediaPickerModal';

    public string $statePath = '';

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
            ['modalId' => $this->modalId],
        ));
    }
}
