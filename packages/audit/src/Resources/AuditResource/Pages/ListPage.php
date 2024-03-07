<?php

namespace Moox\Audit\Resources\AuditResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Audit\Models\ActivityLog;
use Moox\Audit\Resources\AuditResource;
use Moox\Audit\Resources\AuditResource\Widgets\AuditWidgets;

class ListPage extends ListRecords
{
    public static string $resource = AuditResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            AuditWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('audit::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): ActivityLog {
                    return $model::create($data);
                }),
        ];
    }
}
