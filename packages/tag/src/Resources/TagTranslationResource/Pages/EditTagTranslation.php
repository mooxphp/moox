<?php

namespace Moox\Tag\Resources\TagTranslationResource\Pages;

use Moox\Tag\Resources\TagTranslationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
