<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class ContactIdentifierType extends Model implements TranslatableContract
{
    use HasAudit;
    use HasIdentifier;
    use Translatable;

    protected $table = 'contact_identifier_types';

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
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    public static function getResourceName(): string
    {
        return 'contact-identifier-type';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mandatory' => 'boolean',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
        ];
    }
}
