<?php

namespace Moox\Tag\Resources\TagTranslationResource\Pages;

use Moox\Tag\Resources\TagTranslationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
