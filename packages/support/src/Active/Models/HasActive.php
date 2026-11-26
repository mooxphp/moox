<?php

declare(strict_types=1);

namespace Moox\Support\Active\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Active\Exceptions\ActiveException;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Models\MergesColumns;

trait HasActive
{
    use MergesColumns;

    public function initializeHasActive(): void
    {
        $this->mergeColumns(
            ['is_active'],
            ['is_active' => 'boolean'],
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_active'), false);
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function activate(): void
    {
        if ($this instanceof \Astrotomic\Translatable\Contracts\Translatable) {
            if (! $this->translations()->where('is_active', true)->exists()) {
                $translation = $this->translateOrDefault()
                    ?? $this->translateOrNew();
    
                $translation->is_active = true;
                $translation->save();
            }
        }
    
        $this->is_active = true;
        $this->save();
    }
    
    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    
        if ($this instanceof \Astrotomic\Translatable\Contracts\Translatable) {
            $this->translations->each(function ($translation): void {
                $translation->deactivate();
            });
        }
    }
    public static function activeLabel(): string
    {
        $translation = method_exists(static::class, 'getResourceName')
            ? static::getResourceName().'::fields'
            : 'support::active.models';

        return FeatureFields::label($translation, 'is_active', 'active', 'models');
    }
}

