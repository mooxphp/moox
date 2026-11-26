<?php

declare(strict_types=1);

namespace Moox\Support\Title\Forms;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Schema;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Title\Columns\HasTitleColumns;
use Moox\Support\Title\Forms\HasOptionalTitleSlugForm;

trait HasTitleForm
{
    use HasTitleColumns;
    use HasTitleSlugForm;

    protected static function titleSection(): Section
    {
        $translation = static::featureTranslation();

        $repeater = Repeater::make('translations')
            ->label(FeatureFields::label($translation, 'translations', 'title'))
            ->relationship()
            ->schema([
                Select::make('locale')
                    ->label(FeatureFields::label($translation, 'locale', 'title'))
                    ->options(static::titleLocaleOptions())
                    ->required()
                    ->searchable()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                ...static::titleSlugFields(),
                ...static::titleTranslationExtraFields(),
            ])
            ->columns(3)
            ->columnSpanFull();

        $minItems = static::titleTranslationsMinItems();

        if ($minItems !== null) {
            $repeater->minItems($minItems);
        }

        return Section::make(
            FeatureFields::label($translation, 'titles', 'title')
        )->schema([
            $repeater,
        ]);
    }

    protected static function titleTranslationsMinItems(): ?int
    {
        return static::usesOptionalTitleSlug() ? null : 1;
    }

    protected static function usesOptionalTitleSlug(): bool
    {
        return in_array(
            HasOptionalTitleSlugForm::class,
            class_uses_recursive(static::class),
            true,
        );
    }

   /**
 * @return list<Component>
 */
protected static function titleTranslationExtraFields(): array
{
    if (! in_array(
        HasTitleDescriptionForm::class,
        class_uses_recursive(static::class),
        true,
    )) {
        return [];
    }

    return static::titleDescriptionFields();
}

    /**
     * @return array<string, string>
     */
    protected static function titleLocaleOptions(): array
    {
        $localizationClass = 'Moox\\Localization\\Models\\Localization';

        if (! class_exists($localizationClass) || ! Schema::hasTable('localizations')) {
            return [];
        }

        return $localizationClass::query()
            ->where('is_active_admin', true)
            ->whereNotNull('locale_variant')
            ->orderBy('locale_variant')
            ->pluck('locale_variant', 'locale_variant')
            ->all();
    }
}
