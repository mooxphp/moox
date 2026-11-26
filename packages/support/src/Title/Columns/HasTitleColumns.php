<?php

declare(strict_types=1);

namespace Moox\Support\Title\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Moox\Support\Filament\FeatureFields;

trait HasTitleColumns
{
    /**
     * @return list<TextColumn>
     */
    protected static function titleColumns(): array
    {
        $translation = static::featureTranslation();

        return [
            TextColumn::make('title')
                ->label(FeatureFields::label($translation, 'title', 'title', 'columns'))
                ->getStateUsing(fn (Model $record): ?string => static::titleTranslationValue($record, 'title'))
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('translations', function (Builder $query) use ($search): void {
                        $query->where('title', 'like', '%'.$search.'%');
                    });
                }),
            TextColumn::make('slug')
                ->label(FeatureFields::label($translation, 'slug', 'title', 'columns'))
                ->getStateUsing(fn (Model $record): ?string => static::titleTranslationValue($record, 'slug'))
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->whereHas('translations', function (Builder $query) use ($search): void {
                        $query->where('slug', 'like', '%'.$search.'%');
                    });
                }),
        ];
    }

    protected static function titleTranslationValue(Model $record, string $attribute): ?string
    {
        $translations = static::titleTranslations($record);
        $locale = static::titleDisplayLocale();

        $translation = $translations->firstWhere('locale', $locale)
            ?? $translations->first();

        $value = $translation?->getAttribute($attribute);

        return is_string($value) ? $value : null;
    }

    /**
     * @return Collection<int, Model>
     */
    protected static function titleTranslations(Model $record): Collection
    {
        if ($record->relationLoaded('translations')) {
            /** @var Collection<int, Model> $translations */
            $translations = $record->getRelation('translations');

            return $translations;
        }

        if (! method_exists($record, 'translations')) {
            return collect();
        }

        /** @var Collection<int, Model> $translations */
        $translations = $record->translations()->get();

        return $translations;
    }

    protected static function titleDisplayLocale(): string
    {
        $localizationClass = 'Moox\\Localization\\Models\\Localization';

        if (class_exists($localizationClass) && Schema::hasTable('localizations')) {
            $defaultLocale = $localizationClass::query()
                ->where('is_default', true)
                ->value('locale_variant');

            if (is_string($defaultLocale) && $defaultLocale !== '') {
                return $defaultLocale;
            }
        }

        return app()->getLocale();
    }
}
