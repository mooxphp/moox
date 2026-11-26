<?php

declare(strict_types=1);

namespace Moox\Contact\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Relations\HasRelations;
use Moox\Support\Active\Models\HasActive;
use Moox\Support\Audit\Models\HasAudit;
use Moox\Support\Identity\Models\HasIdentifier;
use Moox\Support\SoftDelete\Models\HasSoftDelete;
use Moox\Support\External\Models\HasExternal;
use Moox\Support\Data\Models\HasData;
use Moox\Support\Archive\Models\HasArchive;

class Contact extends Model
{
    use HasActive;
    use HasArchive;
    use HasData;
    use HasExternal;
    use HasAudit;
    use HasIdentifier;
    use HasRelations;
    use HasSoftDelete;

    protected $table = 'contacts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_1',
        'name_2',
        'name_3',
        'note',
        'language_id',
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
        return 'contact';
    }

    public function callingCode(): ?string
    {
        if ($this->country_id === null) {
            return null;
        }

        $country = $this->relationLoaded('country') ? $this->getRelation('country') : $this->country()->first();
        if (! is_object($country)) {
            return null;
        }

        $callingCode = $country->calling_code ?? null;
        if (is_int($callingCode) || is_float($callingCode)) {
            $callingCode = (string) $callingCode;
        }

        if (! is_string($callingCode) || trim($callingCode) === '') {
            return null;
        }

        return trim($callingCode);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'language_id' => 'integer',
            'country_id' => 'integer',
            'organization_type_id' => 'integer',
            'legal_form_id' => 'integer',
        ];
    }
}
