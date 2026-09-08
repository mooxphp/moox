<?php

declare(strict_types=1);

namespace Moox\Media\Resources\MediaCollectionResource\Pages;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Moox\Core\Entities\Items\Static\Pages\BaseListStatic;
use Moox\Media\Resources\MediaCollectionResource;

class ListMediaCollections extends BaseListStatic
{
    protected static string $resource = MediaCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->url(fn (): string => static::$resource::getUrl('create', ['lang' => $this->lang])),
        ];
    }

    public function getTableQuery(): Builder|Relation|null
    {
        return parent::getTableQuery()
            ->whereHas('translations');
    }
}
