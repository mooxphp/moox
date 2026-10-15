<?php

declare(strict_types=1);

namespace Moox\Support\Data;

use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\Validation\ValidationRule;
use Moox\Support\Filament\FeatureFields;

trait HasDataForm
{
    protected static function dataSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'data'))
            ->schema([
                Textarea::make('data')
                    ->label(FeatureFields::label($translation, 'data'))
                    ->rows(8)
                    ->formatStateUsing(function (mixed $state): string {
                        if (is_string($state)) {
                            return $state;
                        }

                        if ($state === null || $state === []) {
                            return '';
                        }

                        $encoded = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                        return is_string($encoded) ? $encoded : '';
                    })
                    ->dehydrateStateUsing(function (mixed $state): ?array {
                        if (! is_string($state) || $state === '' || $state === '{}' || $state === '[]') {
                            return null;
                        }

                        $decoded = json_decode($state, true);

                        return is_array($decoded) ? $decoded : null;
                    })
                    ->dehydrated(fn (mixed $state): bool => is_string($state) && $state !== '' && $state !== '{}' && $state !== '[]')
                    ->rules([
                        ...static::featureRules('data'),
                        static::dataJsonRule($translation),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function dataJsonRule(string $translation): ValidationRule
    {
        return new class($translation) implements ValidationRule
        {
            public function __construct(private string $translation)
            {
            }

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                if ($value === null || $value === '' || is_array($value)) {
                    return;
                }

                if (! is_string($value) || ! json_validate($value)) {
                    $fail(__($this->translation.'.data_invalid'));
                }
            }
        };
    }
}
