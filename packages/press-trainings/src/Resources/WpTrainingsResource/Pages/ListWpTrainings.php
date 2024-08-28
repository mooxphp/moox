<?php

namespace Moox\PressTrainings\Resources\WpTrainingsResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\HasDynamicTabs;
use Moox\PressTrainings\Models\WpTraining;
use Moox\PressWiki\Models\WpWiki;
use Moox\PressTrainings\Resources\WpTrainingResource;

class ListWpTrainings extends ListRecords
{
    use HasDynamicTabs;

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
