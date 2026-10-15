<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactIdentifierTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'contact_identifier_type_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<ContactIdentifierType, $this>
     */
    public function contactIdentifierType(): BelongsTo
    {
        return $this->belongsTo(ContactIdentifierType::class);
    }
}
