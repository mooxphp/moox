<?php

namespace Moox\Tag\Resources\TagTranslationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Moox\Tag\Resources\TagTranslationResource;

class EditTagTranslation extends EditRecord
{
    protected static string $resource = TagTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
