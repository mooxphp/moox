<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VeraPdfValidatable extends MorphPivot
{
    protected $table = 'verapdf_validatables';

    public $incrementing = true;

    protected $fillable = [
        'validatable_type',
        'validatable_id',
        'verapdf_validation_id',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function validatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<VeraPdfValidation, $this>
     */
    public function veraPdfValidation(): BelongsTo
    {
        return $this->belongsTo(VeraPdfValidation::class, 'verapdf_validation_id');
    }
}
