<?php

namespace Adrolli\FilamentJobManager\Resources\WaitingJobsResource\Pages;

use Adrolli\FilamentJobManager\Resources\WaitingJobsResource;
use Adrolli\FilamentJobManager\Resources\WaitingJobsResource\Widgets\JobsWaitingOverview;
use Filament\Resources\Pages\ListRecords;

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
        return __('filament-job-manager::translations.title');
    }
}
