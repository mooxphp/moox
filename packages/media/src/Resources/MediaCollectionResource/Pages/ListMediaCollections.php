<?php

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\Media\Models\MediaCollection;
use Moox\Media\Resources\MediaCollectionResource;

class ListMediaCollections extends ListRecords
{
    protected static string $resource = MediaCollectionResource::class;

    public function mount(): void
    {
        parent::mount();

        MediaCollection::ensureUncategorizedExists();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTableQuery(): Builder|Relation|null
    {
        return parent::getTableQuery()
            ->whereHas('translations', function ($query) {
                $query->where('locale', app()->getLocale());
            });
    }
}
