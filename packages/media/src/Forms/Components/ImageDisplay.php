<?php

namespace Moox\Media\Forms\Components;

use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;
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

        return $record->getFirstMediaUrl('default');
    }
}