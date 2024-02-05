<?php

namespace Moox\Jobs\Resources\JobsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Jobs\Resources\JobsResource;
use Moox\Jobs\Resources\JobsResource\Widgets\JobStatsOverview;

class ListJobs extends ListRecords
{
    public static string $resource = JobsResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            JobStatsOverview::class,
        ];
    }

    public function getTitle(): string
    {
        return __('jobs::translations.title');
    }
}
