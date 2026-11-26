<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;

class ContactRelationshipType extends Model implements TranslatableContract
{
    use HasAudit;
    use HasIdentifier;
    use Translatable;

    protected $table = 'contact_relationship_types';

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
        return 'contact-relationship-type';
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
