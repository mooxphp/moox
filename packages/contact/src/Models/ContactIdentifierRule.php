<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class ContactIdentifierRule extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;

    protected $table = 'contact_identifier_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'contact_identifier_type_id',
        'country_id',
        'organization_type_id',
        'legal_form_id',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function getResourceName(): string
    {
        return 'contact-identifier-rule';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'contact_identifier_type_id' => 'integer',
            'country_id' => 'integer',
            'organization_type_id' => 'integer',
            'legal_form_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
        ];
    }
}
