<?php

declare(strict_types=1);

namespace Moox\Support\Publish\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Moox\Support\Publish\Exceptions\PublishException;
use Moox\Support\Models\MergesColumns;

trait HasPublish
{
    use MergesColumns;

    public function initializeHasPublish(): void
    {
        $this->mergeColumns(
            [
                'published_at',
                'published_by_id',
                'published_by_type',
                'unpublished_at',
                'unpublished_by_id',
                'unpublished_by_type',
            ],
            [
                'published_at' => 'datetime',
                'published_by_id' => 'integer',
                'published_by_type' => 'string',
                'unpublished_at' => 'datetime',
                'unpublished_by_id' => 'integer',
                'unpublished_by_type' => 'string',
            ],
        );
    }
    public function publish(): bool
    {
        if ($this instanceof \Astrotomic\Translatable\Contracts\Translatable) {
            if (! $this->translations()->published()->exists()) {
                $translation = $this->translateOrDefault()
                    ?? $this->translateOrNew();
    
                $translation->publish();
            }
        }
    
        $user = Auth::user();
    
        return $this->forceFill([
            'published_at' => now(),
            'published_by_id' => $user?->getAuthIdentifier(),
            'published_by_type' => $user?->getMorphClass(),
            'unpublished_at' => null,
            'unpublished_by_id' => null,
            'unpublished_by_type' => null,
        ])->save();
    }
    public function unpublish(): bool
    {
        if (method_exists($this, 'getTranslationParent')) {
            $parent = $this->getTranslationParent();
    
            if (
                $parent !== null
                && $parent->isPublished()
                && $this->isPublished()
                && ! $parent->translations()
                    ->where($this->getKeyName(), '!=', $this->getKey())
                    ->published()
                    ->exists()
            ) {
                throw new PublishException(
                    'A model needs at least one published translation.'
                );
            }
        }
    
        $user = Auth::user();
    
        return $this->forceFill([
            'unpublished_at' => now(),
            'unpublished_by_id' => $user?->getAuthIdentifier(),
            'unpublished_by_type' => $user?->getMorphClass(),
        ])->save();
    }
    public function isPublished(): bool
    {
        return $this->published_at !== null
            && $this->unpublished_at === null;
    }

    public function isUnpublished(): bool
    {
        return $this->published_at !== null
            && $this->unpublished_at !== null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->whereNull($this->qualifyColumn('unpublished_at'));
    }

    public function scopeUnpublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull($this->qualifyColumn('published_at'))
            ->whereNotNull($this->qualifyColumn('unpublished_at'));
    }

    public function scopeNotPublished(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->whereNull($this->qualifyColumn('published_at'))
                ->orWhereNotNull($this->qualifyColumn('unpublished_at'));
        });
    }
}