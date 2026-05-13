<?php

namespace Moox\Attribute\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Moox\Attribute\Database\Factories\AttributeFactory;
use Moox\Core\Entities\Items\Draft\BaseDraftModel;
use Moox\Core\Traits\Taxonomy\HasModelTaxonomy;

/**
 * @property string $type
 * @property string[] $translatedAttributes
 * @property string $uuid
 * @property string $ulid
 * @property-read int $author_id
 * @property-read string $author_type
 * @property-read User|null $author
 */
class Attribute extends BaseDraftModel
{
    use HasFactory, HasModelTaxonomy;

    protected function getCustomTranslatedAttributes(): array
    {
        return [
            'value',
        ];
    }

    protected $fillable = [
        'type',
        'name',
        'description',
        'status',
    ];

    public static function getResourceName(): string
    {
        return 'attribute';
    }

    public function values()
    {
        return $this->hasMany(AttributeValues::class);
    }

    protected static function newFactory()
    {
        return AttributeFactory::new();
    }
}
