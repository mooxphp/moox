<?php

declare(strict_types=1);

namespace Moox\Communication\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class CommunicationType extends Model implements TranslatableContract
{
    use HasAudit;
    use HasSoftDelete;
    use Translatable;

    protected $table = 'communication_types';

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
        'mandatory',
        'validation_type',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'mandatory' => false,
    ];

    public static function getResourceName(): string
    {
        return 'communication-type';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mandatory' => 'boolean',
        ];
    }
}
