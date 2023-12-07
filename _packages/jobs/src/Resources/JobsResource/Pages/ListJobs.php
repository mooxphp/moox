<?php

namespace Adrolli\FilamentJobManager\Resources\JobsResource\Pages;

use Adrolli\FilamentJobManager\Resources\JobsResource;
use Adrolli\FilamentJobManager\Resources\JobsResource\Widgets\JobStatsOverview;
use Filament\Resources\Pages\ListRecords;

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
        return __('filament-job-manager::translations.title');
    }
}
