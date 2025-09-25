<?php

declare(strict_types=1);

namespace Moox\Bpmn\View\Components;

use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;
use Illuminate\View\View;
use Moox\Media\Models\Media;
use Moox\Press\Models\WpMedia;

class BpmnViewer extends Component
{
    public function __construct(
        public ?int $mediaId = null,
        public ?int $wpMediaId = null,
        public ?string $filePath = null,
        public string $mode = 'view',
        public string $height = '500px',
        public bool $showToolbar = true,
        public string $class = ''
    ) {
        $this->validateProps();
    }

    public function render(): View
    {
        return view('bpmn::components.bpmn-viewer');
    }

    public function getBpmnContent(): ?string
    {
        if ($this->mediaId) {
            $media = Media::find($this->mediaId);
            if ($media && $media->getPath()) {
                return Storage::disk($media->disk)->get($media->getPath());
            }
        }

        if ($this->wpMediaId) {
            $wpMedia = WpMedia::find($this->wpMediaId);
            if ($wpMedia && $wpMedia->asset) {
                $filePath = public_path($wpMedia->asset);
                if (file_exists($filePath)) {
                    return file_get_contents($filePath);
                }
            }
        }

        if ($this->filePath) {
            if (Storage::disk('public')->exists($this->filePath)) {
                return Storage::disk('public')->get($this->filePath);
            }
        }

        return null;
    }

    public function getBpmnSource(): array
    {
        if ($this->mediaId) {
            return [
                'type' => 'media',
                'id' => $this->mediaId,
            ];
        }

        if ($this->wpMediaId) {
            return [
                'type' => 'wp-media',
                'id' => $this->wpMediaId,
            ];
        }

        if ($this->filePath) {
            return [
                'type' => 'file',
                'path' => $this->filePath,
            ];
        }

        return [
            'type' => 'none',
            'id' => null,
        ];
    }

    public function canEdit(): bool
    {
        return $this->mode === 'edit';
    }

    public function canView(): bool
    {
        return $this->mode === 'view' || $this->mode === 'edit';
    }

    private function validateProps(): void
    {
        $sourceCount = 0;
        if ($this->mediaId) $sourceCount++;
        if ($this->wpMediaId) $sourceCount++;
        if ($this->filePath) $sourceCount++;

        if ($sourceCount === 0) {
            throw new \InvalidArgumentException('At least one of media-id, wp-media-id, or file-path must be provided.');
        }

        if ($sourceCount > 1) {
            throw new \InvalidArgumentException('Only one of media-id, wp-media-id, or file-path can be provided.');
        }

        if (!in_array($this->mode, ['view', 'edit'])) {
            throw new \InvalidArgumentException('Mode must be either "view" or "edit".');
        }
    }
}
