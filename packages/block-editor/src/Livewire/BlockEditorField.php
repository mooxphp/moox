<?php

declare(strict_types=1);

namespace Moox\BlockEditor\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class BlockEditorField extends Component
{
    private const string VIEW = 'moox-editor::livewire.block-editor-field';

    #[Modelable]
    public mixed $state = '[]';

    /**
     * Erlaubte Block-Typen (Whitelist). Muss nicht `positiveBlock` heißen: In Filament-Field-Views
     * existiert bereits eine Variable `$positiveBlock` (Closure der Fluent-Methode `positiveBlock()`).
     *
     * @var array<int, string>|null
     */
    public ?array $allowedBlockTypes = null;

    /**
     * Ausgeschlossene Block-Typen (negativeBlock). Nicht `negativeBlock` benennen (Filament-Closure).
     *
     * @var array<int, string>|null
     */
    public ?array $excludedBlockTypes = null;

    public bool $themeTemplatesEnabled = true;

    public ?string $templateSlug = null;

    public bool $developerJsonEnabled = false;

    public bool $addComponentsEnabled = true;

    public bool $jsonImportEnabled = false;

    public ?string $mediaLibraryApiUrl = null;

    public ?string $mediaLibraryCollection = null;

    public ?string $mediaUsableType = null;

    public ?string $mediaUsableId = null;

    /**
     * @param  array<int, mixed>|null  $allowedBlockTypes
     * @param  array<int, mixed>|null  $excludedBlockTypes
     */
    public function mount(
        ?array $allowedBlockTypes = null,
        ?array $excludedBlockTypes = null,
        bool $themeTemplatesEnabled = true,
        ?string $templateSlug = null,
        bool $developerJsonEnabled = false,
        bool $addComponentsEnabled = true,
        bool $jsonImportEnabled = false,
        ?string $mediaLibraryApiUrl = null,
        ?string $mediaLibraryCollection = null,
        ?string $mediaUsableType = null,
        ?string $mediaUsableId = null,
    ): void {
        $this->allowedBlockTypes = $allowedBlockTypes;
        $this->excludedBlockTypes = $excludedBlockTypes;
        $this->themeTemplatesEnabled = $themeTemplatesEnabled;
        $this->templateSlug = is_string($templateSlug) && trim($templateSlug) !== ''
            ? trim($templateSlug)
            : null;
        $this->developerJsonEnabled = $developerJsonEnabled;
        $this->addComponentsEnabled = $addComponentsEnabled;
        $this->jsonImportEnabled = $jsonImportEnabled;
        $this->mediaLibraryApiUrl = is_string($mediaLibraryApiUrl) && trim($mediaLibraryApiUrl) !== ''
            ? trim($mediaLibraryApiUrl)
            : null;
        $this->mediaLibraryCollection = is_string($mediaLibraryCollection) && trim($mediaLibraryCollection) !== ''
            ? trim($mediaLibraryCollection)
            : null;
        $this->mediaUsableType = is_string($mediaUsableType) && trim($mediaUsableType) !== ''
            ? trim($mediaUsableType)
            : null;
        $this->mediaUsableId = is_string($mediaUsableId) && trim($mediaUsableId) !== ''
            ? trim($mediaUsableId)
            : null;

        if (is_array($this->state)) {
            $this->state = json_encode($this->state, JSON_UNESCAPED_UNICODE);
        }

        if ($this->state === null || $this->state === '') {
            $this->state = '[]';
        }

        if (is_array($allowedBlockTypes)) {
            $this->allowedBlockTypes = array_values(array_filter(
                $allowedBlockTypes,
                static fn (mixed $block): bool => is_string($block) && $block !== ''
            ));
        } else {
            $this->allowedBlockTypes = null;
        }

        if (is_array($excludedBlockTypes)) {
            $this->excludedBlockTypes = array_values(array_filter(
                $excludedBlockTypes,
                static fn (mixed $block): bool => is_string($block) && $block !== ''
            ));
        } else {
            $this->excludedBlockTypes = null;
        }
    }

    public function render(): View
    {
        return view(self::VIEW);
    }
}
