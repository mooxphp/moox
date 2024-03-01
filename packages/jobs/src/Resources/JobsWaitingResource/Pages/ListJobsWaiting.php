<?php

namespace Moox\Jobs\Resources\JobsWaitingResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Jobs\Resources\JobsWaitingResource;
use Moox\Jobs\Resources\JobsWaitingResource\Widgets\JobsWaitingOverview;

class ListJobsWaiting extends ListRecords
{
    public static string $resource = JobsWaitingResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            JobsWaitingOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return __('jobs::translations.jobs_waiting.plural');
    }
}
