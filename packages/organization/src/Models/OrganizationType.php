<?php

declare(strict_types=1);

namespace Moox\Organization\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class OrganizationType extends Model implements TranslatableContract
{
    use HasActive;
    use HasAudit;
    use HasIdentifier;
    use HasSoftDelete;
    use Translatable;

    protected $table = 'organization_types';

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

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function getResourceName(): string
    {
        return 'organization';
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
