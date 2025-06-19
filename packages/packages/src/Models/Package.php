<?php

namespace Moox\Packages\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

class Package extends BaseItemModel
{
    use HasModelTaxonomy;

    protected $table = 'packages';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'slug',
        'name',
        'vendor',
        'version_installed',
        'installed_at',
        'installed_by_id',
        'installed_by_type',
        'updated_by_id',
        'updated_by_type',
        'install_status',
        'update_status',
        'auto_update',
        'is_theme',
        'package_type',
        'activation_steps',
        'update_scheduled_at',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'update_scheduled_at' => 'datetime',
        'activation_steps' => 'json',
        'is_theme' => 'boolean',
        'auto_update' => 'boolean',
        'updated_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->id = Str::uuid();
        });
    }

    public function installed_by(): MorphTo
    {
        return $this->morphTo();
    }

    public function updated_by(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getResourceName(): string
    {
        return 'package';
    }
}
