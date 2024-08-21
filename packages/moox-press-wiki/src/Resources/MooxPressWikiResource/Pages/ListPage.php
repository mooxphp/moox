<?php

namespace Moox\MooxPressWiki\Resources\MooxPressWikiResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\MooxPressWiki\Models\WpWiki;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource;
use Moox\MooxPressWiki\Resources\MooxPressWikiResource\Widgets\MooxPressWikiWidgets;
use Moox\Core\Traits\HasDynamicTabs;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = MooxPressWikiResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            MooxPressWikiWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('moox-press-wiki::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): WpWiki {
                    return $model::create($data);
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('moox-press-wiki.resources.moox-press-wiki.tabs', WpWiki::class);
    }
}
