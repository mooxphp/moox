<?php

declare(strict_types=1);

namespace Moox\Address\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class AddressUsage extends Model
{
    use HasAudit;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'address_usages';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'address_id',
        'address_usage_type_id',
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
        ];
    }
}
