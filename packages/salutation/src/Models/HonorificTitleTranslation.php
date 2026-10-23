<?php

declare(strict_types=1);

namespace Moox\Salutation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HonorificTitleTranslation extends Model
{
    public $timestamps = false;

    protected $table = 'honorific_title_translations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'locale',
        'title',
        'slug',
    ];

    /**
     * @return BelongsTo<HonorificTitle, $this>
     */
    public function honorificTitle(): BelongsTo
    {
        return $this->belongsTo(HonorificTitle::class);
    }
}
