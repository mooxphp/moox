<?php

declare(strict_types=1);

namespace Moox\Support\Relation;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Moox\Support\Filament\FeatureFields;

trait HasRelationForm
{
    protected static function relationSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'relation'))
            ->schema([
                ...static::configuredRelationFields(),
                ...static::additionalRelationFields(),
            ])
            ->columns(2);
    }

    /**
     * @return list<Select|TextInput>
     */
    protected static function additionalRelationFields(): array
    {
        return [];
    }

    /**
     * @return list<Select|TextInput>
     */
    protected static function configuredRelationFields(): array
    {
        $relations = config(static::featureResourceName().'.relations', []);
        $fields = [];

        if (! is_array($relations)) {
            return [];
        }

        foreach ($relations as $relation => $configured) {
            if (! is_string($relation) || ! is_array($configured) || ($configured['kind'] ?? null) !== 'belongs_to') {
                continue;
            }

            $column = $configured['foreign_key'] ?? null;

            if (! is_string($column) || $column === '') {
                continue;
            }

            $fields[] = static::relationField($column, $relation, $configured);
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $configured
     */
    protected static function relationField(string $column, string $relation, array $configured): Select|TextInput
    {
        $translation = static::featureTranslation();
        $model = $configured['model'] ?? null;
        $titleAttribute = $configured['title_attribute'] ?? null;

        if (! is_string($model) || ! is_string($titleAttribute) || ! is_a($model, Model::class, true)) {
            return TextInput::make($column)
                ->label(FeatureFields::label($translation, $column))
                ->numeric()
                ->rules(static::featureRules($column));
        }

        return Select::make($column)
            ->label(FeatureFields::label($translation, $column))
            ->relationship($relation, $titleAttribute)
            ->searchable()
            ->preload()
            ->rules(static::featureRules($column));
    }

    protected static function featureResourceName(): string
    {
        $model = static::getModel();

        if (! method_exists($model, 'getResourceName')) {
            throw new \RuntimeException(sprintf('Model %s must implement getResourceName().', $model));
        }

        return $model::getResourceName();
    }
}
