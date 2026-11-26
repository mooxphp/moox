<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;

class ContactIdentifierRule extends Model
{
    use HasActive;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;

    protected $table = 'contact_identifier_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'contact_identifier_type_id',
        'country_id',
        'organization_type_id',
        'legal_form_id',
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
            'contact_identifier_type_id' => 'integer',
            'country_id' => 'integer',
            'organization_type_id' => 'integer',
            'legal_form_id' => 'integer',
        ];
    }
}
