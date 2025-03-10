<?php

namespace Moox\Item\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class Item extends BaseItemModel
{
    use HasModelTaxonomy;

    protected $fillable = [
        'title',
        'slug',
        'is_active',
        'description',
        'content',
        'data',
        'image',
        'author_id',
        'type',
        'color',
        'due_at',
        'uuid',
        'ulid',
        'status',
    ];

    protected $casts = [
        'slug' => 'string',
        'title' => 'string',
        'is_active' => 'boolean',
        'data' => 'json',
        'due_at' => 'datetime',
        'uuid' => 'string',
        'ulid' => 'string',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
            $model->ulid = (string) \Illuminate\Support\Str::ulid();
        });
    }

    public function getUlidAttribute(): string
    {
        return $this->ulid ?? (string) \Illuminate\Support\Str::ulid();
    }

    public function getUuidAttribute(): string
    {
        return $this->uuid ?? (string) \Illuminate\Support\Str::uuid();
    }

    public static function getResourceName(): string
    {
        return 'item';
    }
}
