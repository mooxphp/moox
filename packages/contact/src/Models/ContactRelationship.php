<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class ContactRelationship extends Model
{
    use HasAudit;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'contact_relationships';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'note',
        'contact_id',
        'related_contact_id',
        'relationship_type_id',
    ];

    public static function getResourceName(): string
    {
        return 'contact-relationship';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contact_id' => 'integer',
            'related_contact_id' => 'integer',
            'relationship_type_id' => 'integer',
        ];
    }
}
