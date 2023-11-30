<?php

namespace Moox\Skeleton\Resources\SkeletonResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Skeleton\Models\Skeleton;
use Moox\Skeleton\Resources\SkeletonResource;
use Moox\Skeleton\Resources\SkeletonResource\Widgets\SkeletonWidgets;

class ListPage extends ListRecords
{
    public static string $resource = SkeletonResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            SkeletonWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('skeleton::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Skeleton {
                    return $model::create($data);
                }),
        ];
    }
}
