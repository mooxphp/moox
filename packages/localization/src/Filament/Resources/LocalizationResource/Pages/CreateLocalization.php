<?php

declare(strict_types=1);

namespace Moox\Localization\Filament\Resources\LocalizationResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Localization\Filament\Resources\LocalizationResource;

class CreateLocalization extends BaseCreateRecord
{
    protected static string $resource = LocalizationResource::class;

    /**
     * Sensible out-of-the-box display defaults (still editable per localization).
     *
     * @return array<string, mixed>
     */
    public static function recommendedDefaults(): array
    {
        return [
            'is_active_admin' => true,
            'is_active_frontend' => false,
            'is_default' => false,
            'use_native_names' => true,
            'show_regional_variants' => true,
            'use_country_translations' => true,
            'use_country_icon' => false,
            'fallback_behaviour' => 'default',
            'language_routing' => 'path',
        ];
    }

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        // Toggle components default to false in Filament; empty fill() leaves create
        // forms looking "all off". Seed the recommended defaults explicitly.
        $this->form->fill(static::recommendedDefaults());

        $this->callHook('afterFill');
    }
}
