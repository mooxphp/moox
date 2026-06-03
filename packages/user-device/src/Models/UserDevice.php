<?php

namespace Moox\UserDevice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Moox\Core\Models\Concerns\HasScopedModel;
use Override;

class UserDevice extends Model
{
    use HasScopedModel;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_devices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'user_id',
        'user_type',
        'user_agent',
        'platform',
        'os',
        'browser',
        'city',
        'country',
        'location',
        'whitelisted',
        'active',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'bool',
        'whitelisted' => 'bool',
        'location' => 'array',
    ];

    /**
     * The "booting" method of the model.
     */
    #[Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $item): void {
            if (filled($item->slug)) {
                return;
            }

            $baseSlug = Str::slug($item->title);
            $slug = $baseSlug;
            $counter = 1;

            while (static::query()->where('slug', $slug)->exists()) {
                $slug = sprintf('%s-%d', $baseSlug, $counter);
                $counter++;
            }

            $item->slug = $slug;
        });

        static::deleting(function (self $item): void {
            if (! Schema::hasTable('sessions') || ! Schema::hasColumn('sessions', 'device_id')) {
                return;
            }

            DB::table('sessions')->where('device_id', $item->getKey())->delete();
        });
    }

    /**
     * Get the owning user model.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active devices.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include whitelisted devices.
     */
    public function scopeWhitelisted(Builder $query): Builder
    {
        return $query->where('whitelisted', true);
    }
}
