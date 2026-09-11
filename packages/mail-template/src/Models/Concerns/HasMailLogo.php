<?php

declare(strict_types=1);

namespace Moox\MailTemplate\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\MailTemplate\Support\MailMedia;
use Moox\Media\Models\Media;
use Moox\Media\Models\MediaUsable;

/**
 * @property mixed $logo
 * @property-read string|null $logo_url
 */
trait HasMailLogo
{
    public function initializeHasMailLogo(): void
    {
        $this->mergeFillable(['logo']);
        $this->mergeCasts(['logo' => 'json']);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return MailMedia::resolveUrl($this->logo);
    }

    public function mediaThroughUsables(): ?BelongsToMany
    {
        if (! class_exists('Moox\Media\Models\Media')) {
            return null;
        }

        return $this->belongsToMany(
            Media::class,
            'media_usables',
            'media_usable_id',
            'media_id',
        )->where('media_usables.media_usable_type', '=', static::class);
    }

    protected static function bootHasMailLogo(): void
    {
        static::deleting(function (Model $model): void {
            if (! class_exists('Moox\Media\Models\MediaUsable')) {
                return;
            }

            $shouldDetach = (method_exists($model, 'isForceDeleting') && $model->isForceDeleting())
                || ! in_array(SoftDeletes::class, class_uses_recursive($model), true);

            if (! $shouldDetach) {
                return;
            }

            MediaUsable::query()
                ->where('media_usable_id', $model->getKey())
                ->where('media_usable_type', $model::class)
                ->delete();
        });
    }
}
