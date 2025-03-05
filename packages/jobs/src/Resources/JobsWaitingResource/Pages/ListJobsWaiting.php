<?php

namespace Moox\Jobs\Resources\JobsWaitingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Jobs\Resources\JobsWaitingResource;
use Moox\Jobs\Resources\JobsWaitingResource\Widgets\JobsWaitingOverview;
use Override;

class ListJobsWaiting extends ListRecords
{
    public static string $resource = JobsWaitingResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            JobsWaitingOverview::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('jobs::translations.jobs_waiting.plural');
    }
}
