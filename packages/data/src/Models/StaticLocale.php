<?php

declare(strict_types=1);

namespace Moox\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Moox\Core\Traits\Base\BaseInModel;
use Moox\Core\Traits\Simple\SingleSimpleInModel;

/**
 * @property-read \Moox\Data\Models\StaticLanguage|null $language
 * @property-read \Moox\Data\Models\StaticCountry|null $country
 */
class StaticLocale extends Model
{
    use BaseInModel, SingleSimpleInModel;

    protected $table = 'static_locales';

    protected $fillable = [
        'language_id',
        'country_id',
        'locale',
        'flag_country_code',
        'name',
        'is_official_language',
    ];

    public function language()
    {
        return $this->belongsTo(StaticLanguage::class);
    }

    public function country()
    {
        return $this->belongsTo(StaticCountry::class);
    }

    protected $casts = [];

    public function getLanguageFlagIconAttribute(): ?string
    {
        return match ($this->language?->alpha2) {
            'ar' => 'flag-ar_arab',
            default => @file_exists(base_path("packages/flag-icons-circle/resources/svg/{$this->language?->alpha2}.svg"))
                ? "flag-{$this->language?->alpha2}"
                : (@file_exists(base_path("packages/flag-icons-circle/resources/svg/{$this->country?->alpha2}.svg"))
                    ? "flag-{$this->country?->alpha2}"
                    : null),
        };
    }

    public function getCountryFlagIconAttribute(): ?string
    {
        return @file_exists(base_path("packages/flag-icons-circle/resources/svg/{$this->country?->alpha2}.svg"))
            ? "flag-{$this->country?->alpha2}"
            : null;
    }
}
