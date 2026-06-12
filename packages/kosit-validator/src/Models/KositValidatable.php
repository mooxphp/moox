<?php

declare(strict_types=1);

namespace Moox\KositValidator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KositValidatable extends MorphPivot
{
    protected $table = 'kosit_validatables';

    public $incrementing = true;

    protected $fillable = [
        'validatable_type',
        'validatable_id',
        'kosit_validation_id',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function validatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<KositValidation, $this>
     */
    public function kositValidation(): BelongsTo
    {
        return $this->belongsTo(KositValidation::class);
    }
}
