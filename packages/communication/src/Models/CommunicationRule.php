<?php

declare(strict_types=1);

namespace Moox\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;
use Moox\Support\Identity\HasIdentifier;

class CommunicationRule extends Model
{
    use HasAudit;
    use HasIdentifier;
    use HasRelations;

    protected $table = 'communication_rules';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'is_active',
        'owner_type',
        'communication_type_id',
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
        return 'communication-rule';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'communication_type_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
        ];
    }
}
