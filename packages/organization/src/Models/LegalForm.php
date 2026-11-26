<?php

declare(strict_types=1);

namespace Moox\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;

class LegalForm extends Model
{
    use HasActive;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'legal_forms';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'country_id',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    public static function getResourceName(): string
    {
        return 'legal-form';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
        ];
    }
}
