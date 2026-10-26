<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;

class ContactIdentifier extends Model
{
    use HasRelations;

    public $timestamps = false;

    protected $table = 'contact_identifiers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'value',
        'contact_id',
        'contact_identifier_type_id',
        'country_id',
    ];

    public static function getResourceName(): string
    {
        return 'contact-identifier';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_id' => 'integer',
            'contact_identifier_type_id' => 'integer',
            'country_id' => 'integer',
        ];
    }
}
