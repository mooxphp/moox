<?php

declare(strict_types=1);

namespace Moox\Support\Archive\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Moox\Support\Models\MergesColumns;

trait HasArchive
{
    use MergesColumns;

    public function initializeHasArchive(): void
    {
        $this->mergeColumns(
            ['archived_at', 'archived_by_id', 'archived_by_type'],
            [
                'archived_at' => 'datetime',
                'archived_by_id' => 'integer',
                'archived_by_type' => 'string',
            ],
        );
    }

    public function archive(): bool
    {
        $user = Auth::user();

        return $this->forceFill([
            'archived_at' => now(),
            'archived_by_id' => $user?->getAuthIdentifier(),
            'archived_by_type' => $user ? $user::class : null,
        ])->save();
    }

    public function unarchive(): bool
    {
        return $this->forceFill([
            'archived_at' => null,
            'archived_by_id' => null,
            'archived_by_type' => null,
        ])->save();
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull(
            $this->qualifyColumn('archived_at')
        );
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull(
            $this->qualifyColumn('archived_at')
        );
    }
}