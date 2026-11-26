<?php

declare(strict_types=1);

namespace Moox\Salutation\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class HonorificTitle extends Model implements TranslatableContract
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;
    use Translatable;

    protected $table = 'honorific_titles';

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
        'salutation_id',
        'sort_order',
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
        return 'honorific-title';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salutation_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
