<?php

declare(strict_types=1);

namespace Moox\Bpmn\View\Components;

use Illuminate\View\View;
use Moox\Media\Models\Media;
use Illuminate\View\Component;
use Moox\Press\Models\WpMedia;
use Illuminate\Support\Facades\Storage;

class BpmnViewer extends Component
{
    public function __construct(
        public ?int $mediaId = null,
        public ?int $wpMediaId = null,
        public ?string $filePath = null,
        public string $mode = 'view',
        public string $height = '900px',
        public bool $showToolbar = true,
        public string $class = '',
    ) {
        $this->validateProps();
    }

    
    public function render(): View
    {
        return view('bpmn::components.bpmn-viewer', [
            'bpmnSource'  => $this->getBpmnSource(),
            'canEdit'     => $this->canEdit(),
            'canView'     => $this->canView(),
            'bpmnContent' => $this->getBpmnContent(),
        ]);
    }

    /**
     * Get the BPMN XML content from the selected source.
     */
    public function getBpmnContent(): ?string
    {
        $content = null;

        if ($this->mediaId) {
            $media = Media::find($this->mediaId);
            if ($media && $media->getPath() && Storage::disk($media->disk)->exists($media->getPath())) {
                $content = Storage::disk($media->disk)->get($media->getPath());
            }
        } elseif ($this->wpMediaId) {
            $wpMedia = WpMedia::find($this->wpMediaId);
            if ($wpMedia && $wpMedia->asset) {
                $filePath = public_path($wpMedia->asset);
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                }
            }
        } elseif ($this->filePath) {
            // Normalize path
            $normalized = str_replace(['\\', 'storage/app/public/'], ['/', ''], $this->filePath);

            if (Storage::disk('public')->exists($normalized)) {
                $content = Storage::disk('public')->get($normalized);
            } else {
                // fallback: absolute path
                $absolutePath = storage_path('app/public/' . $normalized);
                if (file_exists($absolutePath)) {
                    $content = file_get_contents($absolutePath);
                }
            }
        }

        return $content ?: null;
    }

    /**
     * Define the BPMN source meta for the Blade view.
     */
    public function getBpmnSource(): array
    {
        if ($this->mediaId) {
            return [
                'type' => 'media',
                'id'   => $this->mediaId,
            ];
        }

        if ($this->wpMediaId) {
            return [
                'type' => 'wp-media',
                'id'   => $this->wpMediaId,
            ];
        }

        if ($this->filePath) {
            return [
                'type' => 'file',
                'path' => str_replace('\\', '/', $this->filePath),
            ];
            
        }
        
        return [
            'type' => 'none',
            'id'   => null,
        ];
    }

    public function canEdit(): bool
    {
        return $this->mode === 'edit';
    }

    public function canView(): bool
    {
        return in_array($this->mode, ['view', 'edit']);
    }

    /**
     * Validate the input props to ensure only one source is defined.
     */
    private function validateProps(): void
    {
        $sources = array_filter([
            $this->mediaId,
            $this->wpMediaId,
            $this->filePath,
        ]);

        if (count($sources) === 0) {
            throw new \InvalidArgumentException(
                'You must provide one of mediaId, wpMediaId, or filePath.'
            );
        }

        if (count($sources) > 1) {
            throw new \InvalidArgumentException(
                'Only one of mediaId, wpMediaId, or filePath can be provided at a time.'
            );
        }

        if (!in_array($this->mode, ['view', 'edit'])) {
            throw new \InvalidArgumentException('Mode must be either "view" or "edit".');
        }
    }
}
