<?php

namespace Moox\PressTrainings\Resources\WpTrainingsResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\HasListPageTabs;
use Moox\PressTrainings\Models\WpTraining;
use Moox\PressTrainings\Resources\WpTrainingResource;

class ListWpTrainings extends ListRecords
{
    use HasListPageTabs;

    protected static string $resource = WpTrainingResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('new-post')->label('New Post')->url('/wp/wp-admin/post-new.php?post_type=wiki')];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-trainings.resources.wiki.tabs', WpTraining::class);
    }
}
