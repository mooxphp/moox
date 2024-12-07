<?php

declare(strict_types=1);

namespace Moox\Core\Traits\SoftDelete;

use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;

trait SingleSoftDeleteInListPage
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create'),
        ];
    }

    protected function applyStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            'deleted' => $query->onlyTrashed(),
            default => $query->whereNull('deleted_at'),
        };
    }
}
