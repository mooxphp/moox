<?php

declare(strict_types=1);

namespace Moox\Support\Identity;

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
}
