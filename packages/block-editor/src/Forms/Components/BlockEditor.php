<?php

namespace Moox\BlockEditor\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class BlockEditor extends Field
{
    protected string $view = 'moox-editor::forms.components.block-editor';

    /**
     * @var array<int, string>|Closure|null
     */
    protected array|Closure|null $positiveBlockConfiguration = null;

    /**
     * Block-Typen, die in der Auswahl nicht angeboten werden (Blacklist).
     *
     * @var array<int, string>|Closure|null
     */
    protected array|Closure|null $negativeBlockConfiguration = null;

    /**
     * Theme-/Vorlagen-Auswahl (Toolbar-Tab „Theme Vorlagen“, Theme speichern, etc.).
     */
    protected bool|Closure $templatesConfiguration = true;

    /**
     * Optionaler Template-Slug, der beim Initialisieren geladen werden soll.
     */
    protected string|Closure|null $templateSlugConfiguration = null;

    /**
     * Steuert, ob die Developer-JSON-Ansicht im Editor verfügbar ist.
     */
    protected bool|Closure $developerJsonConfiguration = false;

    /**
     * Steuert, ob neue Block-Komponenten hinzugefügt werden dürfen.
     */
    protected bool|Closure $addComponentsConfiguration = true;

    /**
     * Steuert, ob der JSON-Import im Editor verfügbar ist.
     */
    protected bool|Closure $jsonImportConfiguration = false;

    /**
     * API-Endpoint der Mediathek (z. B. /api/media).
     */
    protected string|Closure|null $mediaLibraryApiUrlConfiguration = '/api/media';

    /**
     * Default-Collection für Mediathek-Abfragen.
     */
    protected string|Closure|null $mediaLibraryCollectionConfiguration = null;

    /**
     * Optionaler Kontext-Typ für media_usables.media_usable_type.
     */
    protected string|Closure|null $mediaUsableTypeConfiguration = null;

    /**
     * Optionale Kontext-ID für media_usables.media_usable_id.
     */
    protected string|int|Closure|null $mediaUsableIdConfiguration = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default('[]');

        $this->rules(['nullable', 'json']);

        $this->formatStateUsing(function (mixed $state): string {
            if (blank($state)) {
                return '[]';
            }

            if (is_string($state)) {
                return $state;
            }

            return json_encode($state, JSON_UNESCAPED_UNICODE) ?: '[]';
        });

        $this->dehydrateStateUsing(function (mixed $state): string {
            if (blank($state)) {
                return '[]';
            }

            if (is_string($state)) {
                return $state;
            }

            return json_encode($state, JSON_UNESCAPED_UNICODE) ?: '[]';
        });
    }

    /**
     * @param  array<int, string>|Closure|null  $positiveBlock
     */
    public function positiveBlock(array|Closure|null $positiveBlock): static
    {
        $this->positiveBlockConfiguration = $positiveBlock;

        return $this;
    }

    /**
     * @return array<int, string>|null
     */
    public function getPositiveBlock(): ?array
    {
        $positiveBlock = $this->evaluate($this->positiveBlockConfiguration);

        if (! is_array($positiveBlock)) {
            return null;
        }

        return array_values(array_filter(
            $positiveBlock,
            static fn (mixed $block): bool => is_string($block) && $block !== ''
        ));
    }

    /**
     * @param  array<int, string>|Closure|null  $negativeBlock
     */
    public function negativeBlock(array|Closure|null $negativeBlock): static
    {
        $this->negativeBlockConfiguration = $negativeBlock;

        return $this;
    }

    /**
     * @return array<int, string>|null
     */
    public function getNegativeBlock(): ?array
    {
        $negativeBlock = $this->evaluate($this->negativeBlockConfiguration);

        if (! is_array($negativeBlock)) {
            return null;
        }

        return array_values(array_filter(
            $negativeBlock,
            static fn (mixed $block): bool => is_string($block) && $block !== ''
        ));
    }

    /**
     * Steuert, ob Nutzer Theme-Vorlagen einsehen, laden und speichern dürfen (`false` = deaktiviert).
     */
    public function templates(bool|Closure $enabled): static
    {
        $this->templatesConfiguration = $enabled;

        return $this;
    }

    public function getTemplatesEnabled(): bool
    {
        return (bool) $this->evaluate($this->templatesConfiguration);
    }

    public function templateSlug(string|Closure|null $slug): static
    {
        $this->templateSlugConfiguration = $slug;

        return $this;
    }

    public function getTemplateSlug(): ?string
    {
        $slug = $this->evaluate($this->templateSlugConfiguration);

        if (! is_string($slug)) {
            return null;
        }

        $normalizedSlug = trim($slug);

        return $normalizedSlug !== '' ? $normalizedSlug : null;
    }

    /**
     * Aktiviert / deaktiviert die JSON-Ansicht im Editor.
     */
    public function showJson(bool|Closure $enabled = true): static
    {
        $this->developerJsonConfiguration = $enabled;

        return $this;
    }

    public function getDeveloperJsonEnabled(): bool
    {
        return (bool) $this->evaluate($this->developerJsonConfiguration);
    }

    /**
     * Aktiviert / deaktiviert das Hinzufügen neuer Block-Komponenten.
     */
    public function addComponents(bool|Closure $enabled = true): static
    {
        $this->addComponentsConfiguration = $enabled;

        return $this;
    }

    public function getAddComponentsEnabled(): bool
    {
        return (bool) $this->evaluate($this->addComponentsConfiguration);
    }

    /**
     * Aktiviert / deaktiviert den JSON-Import im Editor.
     */
    public function showJsonImport(bool|Closure $enabled = true): static
    {
        $this->jsonImportConfiguration = $enabled;

        return $this;
    }

    public function getJsonImportEnabled(): bool
    {
        return (bool) $this->evaluate($this->jsonImportConfiguration);
    }

    public function mediaLibraryApiUrl(string|Closure|null $url): static
    {
        $this->mediaLibraryApiUrlConfiguration = $url;

        return $this;
    }

    public function getMediaLibraryApiUrl(): ?string
    {
        $url = $this->evaluate($this->mediaLibraryApiUrlConfiguration);

        if (! is_string($url)) {
            return null;
        }

        $normalizedUrl = trim($url);

        return $normalizedUrl !== '' ? $normalizedUrl : null;
    }

    public function mediaLibraryCollection(string|Closure|null $collection): static
    {
        $this->mediaLibraryCollectionConfiguration = $collection;

        return $this;
    }

    public function getMediaLibraryCollection(): ?string
    {
        $collection = $this->evaluate($this->mediaLibraryCollectionConfiguration);

        if (! is_string($collection)) {
            return null;
        }

        $normalizedCollection = trim($collection);

        return $normalizedCollection !== '' ? $normalizedCollection : null;
    }

    public function mediaUsableType(string|Closure|null $type): static
    {
        $this->mediaUsableTypeConfiguration = $type;

        return $this;
    }

    public function getMediaUsableType(): ?string
    {
        $type = $this->evaluate($this->mediaUsableTypeConfiguration);

        if (! is_string($type)) {
            return null;
        }

        $normalizedType = trim($type);

        return $normalizedType !== '' ? $normalizedType : null;
    }

    public function mediaUsableId(string|int|Closure|null $id): static
    {
        $this->mediaUsableIdConfiguration = $id;

        return $this;
    }

    public function getMediaUsableId(): ?string
    {
        $id = $this->evaluate($this->mediaUsableIdConfiguration);

        if (! is_string($id) && ! is_int($id)) {
            return null;
        }

        $parsedId = (int) $id;

        return $parsedId > 0 ? (string) $parsedId : null;
    }
}
