<?php

declare(strict_types=1);

namespace Moox\Support\Identity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasIdentifier
{
    protected static function bootHasIdentifier(): void
    {
        static::creating(static function (Model $model): void {
            if (! filled($model->getAttribute('ulid'))) {
                $model->setAttribute('ulid', (string) Str::ulid());
            }

            if (! filled($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid());
            }
        });

        static::updating(static function (Model $model): void {
            foreach (['ulid', 'uuid'] as $attribute) {
                if ($model->isDirty($attribute)) {
                    $model->setAttribute($attribute, $model->getOriginal($attribute));
                }
            }
        });
    }

    public function hasUlid(): bool
    {
        return filled($this->getAttribute('ulid'));
    }

    public function hasUuid(): bool
    {
        return filled($this->getAttribute('uuid'));
    }

    public function isIdentifier(string $value): bool
    {
        return in_array($value, [
            $this->getAttribute('ulid'),
            $this->getAttribute('uuid'),
        ], true);
    }

    public function scopeWithIdentifier(Builder $query, string $identifier): Builder
    {
        return $query->where(static function (Builder $query) use ($identifier): void {
            $query
                ->where('ulid', $identifier)
                ->orWhere('uuid', $identifier);
        });
    }
}