<?php

declare(strict_types=1);

namespace Moox\Supplier\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class Supplier extends Model
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'suppliers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'supplier_type',
        'supplier_name',
        'supplier_number',
        'note',
        'contact_id',
        'language_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function getResourceName(): string
    {
        return 'supplier';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_id' => 'integer',
            'language_id' => 'integer',
        ];
    }
}
