<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Support\Audit\HasAudit;

class AddressUsageType extends Model implements TranslatableContract
{
    use HasAudit;
    use SoftDeletes;
    use Translatable;

    protected $table = 'address_usage_types';

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'slug',
    ];

    public bool $useTranslationFallback = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    public static function getResourceName(): string
    {
        return 'address-usage-type';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'restored_at' => 'datetime',
        ];
    }
}
