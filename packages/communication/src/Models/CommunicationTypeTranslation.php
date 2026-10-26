<?php

declare(strict_types=1);

namespace Moox\Communication\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationTypeTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'communication_type_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<CommunicationType, $this>
     */
    public function communicationType(): BelongsTo
    {
        return $this->belongsTo(CommunicationType::class);
    }
}
