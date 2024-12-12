<?php

namespace Moox\PressTrainings\Resources\WpTrainingsTopicResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Traits\Tabs\TabsInListPage;
use Moox\PressTrainings\Models\WpTrainingsTopic;
use Moox\PressTrainings\Resources\WpTrainingsTopicResource;

class ListWpTrainingsTopics extends ListRecords
{
    use TabsInListPage;

    protected static string $resource = WpTrainingsTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('press-trainings.resources.topic.tabs', WpTrainingsTopic::class);
    }
}
