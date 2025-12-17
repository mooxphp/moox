<?php

declare(strict_types=1);

namespace Moox\Bpmn\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;
use Moox\Media\Models\Media;
use Moox\Press\Models\WpMedia;
use Illuminate\View\View;

class BpmnViewer extends Field
{
    protected string $view = 'bpmn::components.bpmn-viewer';

    protected string $mediaIntegration = 'file';

    protected string $mode = 'full';

    protected ?string $filePath = null;

    protected ?string $fileDirectory = 'bpmn';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (self $component, $state) {
            $component->updateBpmnViewerState($state);
        });

        $this->afterStateUpdated(function (self $component, $state) {
            $component->updateBpmnViewerState($state);
        });

        // Handle BPMN content saving
        $this->saveRelationshipsUsing(function (self $component, $state) {
            $bpmnContent = request()->input('bpmn_content');
            if ($bpmnContent && $component->canEdit()) {
                $component->saveBpmnContent($bpmnContent);
            }
        });
    }

    public function mediaIntegration(string $integration): static
    {
        $this->mediaIntegration = $integration;

        return $this;
    }

    public function getMediaIntegration(): string
    {
        return $this->mediaIntegration;
    }

    public function mode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function filePath(string $path): static
    {
        $this->filePath = $path;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function fileDirectory(string $directory): static
    {
        $this->fileDirectory = $directory;

        return $this;
    }

    public function getFileDirectory(): string
    {
        return $this->fileDirectory;
    }

    public function editMode(): static
    {
        return $this->mode('edit');
    }

    public function uploadMode(): static
    {
        return $this->mode('upload');
    }

    public function fullMode(): static
    {
        return $this->mode('full');
    }

    public function mooxIntegration(): static
    {
        return $this->mediaIntegration('moox');
    }

    public function pressIntegration(): static
    {
        return $this->mediaIntegration('press');
    }

    public function fileIntegration(): static
    {
        return $this->mediaIntegration('file');
    }

    public function render(): View
    {
        return view('bpmn::components.bpmn-viewer', [
            'bpmnSource' => $this->getBpmnSource(),
            'canEdit' => $this->canEdit(),
            'canView' => $this->canView(),
            'bpmnContent' => $this->getBpmnContent(),
        ]);
    }

    public function getBpmnSource(): array
    {
        $state = $this->getState();

        if ($this->mediaIntegration === 'moox' && $state) {
            return [
                'type' => 'media',
                'id' => $state,
            ];
        }

        if ($this->mediaIntegration === 'press' && $state) {
            return [
                'type' => 'wp-media',
                'id' => $state,
            ];
        }

        if ($this->mediaIntegration === 'file' && $state) {
            return [
                'type' => 'file',
                'path' => $this->getFullFilePath(),
            ];
        }

        return [
            'type' => 'none',
            'id' => null,
        ];
    }

    public function canEdit(): bool
    {
        return in_array($this->mode, ['edit', 'full']);
    }

    public function canUpload(): bool
    {
        return in_array($this->mode, ['upload', 'full']);
    }

    public function canView(): bool
    {
        return true;
    }

    public function getFullFilePath(): string
    {
        if ($this->filePath) {
            return $this->filePath;
        }

        $state = $this->getState();
        $filename = $state ?: 'default.bpmn';

        return $this->fileDirectory.'/'.$filename;
    }

    public function getBpmnContent(): ?string
    {
        $source = $this->getBpmnSource();

        if ($source['type'] === 'media' && $source['id']) {
            $media = Media::find($source['id']);
            if ($media && $media->getPath()) {
                return Storage::disk($media->disk)->get($media->getPath());
            }
        }

        if ($source['type'] === 'wp-media' && $source['id']) {
            $wpMedia = WpMedia::find($source['id']);
            if ($wpMedia && $wpMedia->asset) {
                $filePath = public_path($wpMedia->asset);
                if (file_exists($filePath)) {
                    return file_get_contents($filePath);
                }
            }
        }

        if ($source['type'] === 'file' && $source['path']) {
            if (Storage::disk('public')->exists($source['path'])) {
                return Storage::disk('public')->get($source['path']);
            }
        }

        return null;
    }

    public function saveBpmnContent(string $content): bool
    {
        $state = $this->getState();

        if ($this->mediaIntegration === 'moox' && $state) {
            $media = Media::find($state);
            if ($media && $media->getPath()) {
                return Storage::disk($media->disk)->put($media->getPath(), $content) !== false;
            }
        }

        if ($this->mediaIntegration === 'press' && $state) {
            $wpMedia = WpMedia::find($state);
            if ($wpMedia && $wpMedia->asset) {
                $filePath = public_path($wpMedia->asset);

                return file_put_contents($filePath, $content) !== false;
            }
        }

        if ($this->mediaIntegration === 'file') {
            $filePath = $this->getFullFilePath();

            return Storage::disk('public')->put($filePath, $content) !== false;
        }

        return false;
    }

    protected function updateBpmnViewerState($state): void
    {
        // This method can be used to update the BPMN viewer state
        // when the field state changes
    }

    public function getViewData(): array
    {
        $source = $this->getBpmnSource();

        return [
            'mediaId' => $source['type'] === 'media' ? $source['id'] : null,
            'wpMediaId' => $source['type'] === 'wp-media' ? $source['id'] : null,
            'filePath' => $source['type'] === 'file' ? $source['path'] : null,
            'mode' => $this->canEdit() ? 'edit' : 'view',
            'height' => '500px',
            'showToolbar' => $this->canEdit(),
            'class' => 'filament-bpmn-viewer',
        ];
    }
}
