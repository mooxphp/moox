<?php

declare(strict_types=1);

namespace Moox\Salutation\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class Salutation extends Model implements TranslatableContract
{
    use HasAudit;
    use HasIdentifier;
    use SoftDeletes;
    use Translatable;

    protected $table = 'salutations';

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'slug',
        'salutation',
        'formal_salutation',
    ];

    public bool $useTranslationFallback = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'data',
        'sort_order',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'archived_at',
        'archived_by_id',
        'archived_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'data' => '{}',
        'sort_order' => 0,
    ];

    public static function getResourceName(): string
    {
        return 'salutation';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'data' => 'array',
            'sort_order' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'archived_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }
}
