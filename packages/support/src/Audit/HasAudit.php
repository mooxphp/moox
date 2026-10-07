<?php

declare(strict_types=1);

namespace Moox\Support\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasAudit
{
    protected static function bootHasAudit(): void
    {
        static::creating(static function (Model $model): void {
            $user = Auth::user();

            if ($model->getAttribute('created_by_id') === null && $user !== null) {
                $model->setAttribute('created_by_id', (int) $user->getAuthIdentifier());
                $model->setAttribute('created_by_type', $user::class);
            }

            if ($model->getAttribute('updated_by_id') === null) {
                $model->setAttribute('updated_by_id', $model->getAttribute('created_by_id'));
                $model->setAttribute('updated_by_type', $model->getAttribute('created_by_type'));
            }
        });

        static::updating(static function (Model $model): void {
            foreach (['created_at', 'created_by_id', 'created_by_type'] as $attribute) {
                if ($model->isDirty($attribute)) {
                    $model->setAttribute($attribute, $model->getOriginal($attribute));
                }
            }

            $user = Auth::user();

            if ($user === null) {
                return;
            }

            $model->setAttribute('updated_by_id', (int) $user->getAuthIdentifier());
            $model->setAttribute('updated_by_type', $user::class);
        });
    }
}
