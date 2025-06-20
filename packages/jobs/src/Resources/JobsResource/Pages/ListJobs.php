<?php

namespace Moox\Jobs\Resources\JobsResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Jobs\Resources\JobsResource;
use Moox\Jobs\Resources\JobsResource\Widgets\JobStatsOverview;
use Override;

class ListJobs extends ListRecords
{
    public static string $resource = JobsResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            JobStatsOverview::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('jobs::translations.jobs.plural');
    }
}
