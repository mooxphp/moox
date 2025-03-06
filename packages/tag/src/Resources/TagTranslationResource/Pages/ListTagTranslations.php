<?php

namespace Moox\Tag\Resources\TagTranslationResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Moox\Tag\Resources\TagTranslationResource;

class ListTagTranslations extends ListRecords
{
    protected static string $resource = TagTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
