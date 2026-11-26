<?php

declare(strict_types=1);

namespace Moox\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class Customer extends Model
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'customers';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'customer_type',
        'customer_name',
        'customer_number',
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
        return 'customer';
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

    public function displayLabel(): string
    {
        return $this->customer_name.' ('.$this->customer_number.')';
    }
}
