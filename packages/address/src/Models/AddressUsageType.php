<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class AddressUsageType extends Model implements TranslatableContract
{
    use HasAudit;
    use HasSoftDelete;
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
        ];
    }
}
