<?php

namespace Moox\Jobs\Resources\WaitingJobsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Jobs\Resources\WaitingJobsResource;
use Moox\Jobs\Resources\WaitingJobsResource\Widgets\JobsWaitingOverview;

class ListJobsWaiting extends ListRecords
{
    public static string $resource = WaitingJobsResource::class;

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
        return __('jobs::translations.title');
    }
}
