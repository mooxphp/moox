<?php

declare(strict_types=1);

namespace Moox\Support\Title\Forms;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Component;
use Moox\Support\Filament\FeatureFields;

trait HasTitleDescriptionForm
{
    /**
     * @return list<Component>
     */
    protected static function titleDescriptionFields(): array
    {
        return [
            static::titleDescriptionField(),
        ];
    }

    protected static function titleDescriptionField(): Textarea
    {
        $translation = static::featureTranslation();

        return Textarea::make('description')
            ->label(FeatureFields::label($translation, 'description', 'title'))
            ->rows(4)
            ->rules(static::featureRules('description'))
            ->columnSpanFull();
    }
}