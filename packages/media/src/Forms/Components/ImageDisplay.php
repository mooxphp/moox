<?php

namespace Moox\Media\Forms\Components;

use Filament\Forms\Components\Field;

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

        if (!$record) {
            return null;
        }

        $collection = $record->collection_name ?? 'default';

        return $record->getFirstMediaUrl($collection);
    }
}
