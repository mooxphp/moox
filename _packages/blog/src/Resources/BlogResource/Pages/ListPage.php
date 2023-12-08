<?php

namespace Moox\Blog\Resources\BlogResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Blog\Models\Blog;
use Moox\Blog\Resources\BlogResource;
use Moox\Blog\Resources\BlogResource\Widgets\BlogWidgets;

class ListPage extends ListRecords
{
    public static string $resource = BlogResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            BlogWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('blog::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Blog {
                    return $model::create($data);
                }),
        ];
    }
}
