<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactRelationshipTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'contact_relationship_type_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<ContactRelationshipType, $this>
     */
    public function contactRelationshipType(): BelongsTo
    {
        return $this->belongsTo(ContactRelationshipType::class);
    }
}
