<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\Field;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class ImageDisplay extends Field
{
    protected string $view = 'media::forms.components.image-display';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrated(false);
    }

    public function getState(): ?string
    {
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if (! $record instanceof SpatieMedia) {
            return null;
        }

        return $record->getUrl();
    }
}
