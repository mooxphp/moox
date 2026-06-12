<?php

declare(strict_types=1);

namespace Moox\KositValidator\Resources\KositValidationResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Moox\KositValidator\Support\KositRelationConfig;
use Override;

class KositValidatablesRelationManager extends RelationManager
{
    protected static string $relationship = 'kositValidatables';

    #[Override]
    public static function getRelationshipName(): string
    {
        return KositRelationConfig::relationshipName();
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) (KositRelationConfig::kositValidatables()['label'] ?? __('kosit-validator::fields.validatables'));
    }

    public function form(Schema $schema): Schema
    {
        $ownerTypes = KositRelationConfig::ownerTypes();
        $morphName = KositRelationConfig::morphName();

        $fields = [
            MorphToSelect::make($morphName)
                ->label(__('kosit-validator::fields.owner'))
                ->types(
                    collect($ownerTypes)
                        ->map(fn (string $label, string $class): Type => Type::make($class)->label($label))
                        ->values()
                        ->all()
                )
                ->required()
                ->visible(fn (): bool => $ownerTypes !== []),
        ];

        foreach (KositRelationConfig::pivotColumns() as $column) {
            $fields[] = Checkbox::make($column)
                ->label(__('kosit-validator::fields.'.$column));
        }

        return $schema->components($fields);
    }

    public function table(Table $table): Table
    {
        $morphName = KositRelationConfig::morphName();

        $columns = [
            TextColumn::make("{$morphName}_type")
                ->label(__('kosit-validator::fields.owner'))
                ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                ->searchable(),
            TextColumn::make("{$morphName}_id")
                ->label('ID')
                ->searchable(),
            TextColumn::make("{$morphName}")
                ->label(__('kosit-validator::fields.owner_name'))
                ->formatStateUsing(function ($record) use ($morphName) {
                    if ($record->{$morphName} && method_exists($record->{$morphName}, 'displayLabel')) {
                        return $record->{$morphName}->displayLabel();
                    }
                    if ($record->{$morphName} && property_exists($record->{$morphName}, 'name')) {
                        return $record->{$morphName}->name;
                    }

                    return (string) ($record->{$morphName.'_id'} ?? '');
                })
                ->searchable(),
        ];

        foreach (KositRelationConfig::pivotColumns() as $column) {
            $columns[] = IconColumn::make($column)
                ->label(__('kosit-validator::fields.'.$column))
                ->boolean();
        }

        return $table
            ->columns($columns)
            ->headerActions([
                CreateAction::make()
                    ->label(__('kosit-validator::fields.add_validatable'))
                    ->visible(fn (): bool => KositRelationConfig::ownerTypes() !== []),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
