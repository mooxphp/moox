<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\HasAudit;

class AddressUsage extends Model
{
    use HasAudit;
    use HasRelations;
    use SoftDeletes;

    protected $table = 'address_usages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'address_id',
        'address_usage_type_id',
        'created_by_id',
        'created_by_type',
        'updated_by_id',
        'updated_by_type',
        'deleted_at',
        'deleted_by_id',
        'deleted_by_type',
        'restored_at',
        'restored_by_id',
        'restored_by_type',
    ];

    public static function getResourceName(): string
    {
        return 'address-usage';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'address_id' => 'integer',
            'address_usage_type_id' => 'integer',
            'created_by_id' => 'integer',
            'updated_by_id' => 'integer',
            'deleted_by_id' => 'integer',
            'restored_by_id' => 'integer',
            'restored_at' => 'datetime',
        ];
    }
}
