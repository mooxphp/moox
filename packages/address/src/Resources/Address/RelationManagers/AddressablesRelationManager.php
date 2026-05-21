<?php

declare(strict_types=1);

namespace Moox\Address\Resources\Address\RelationManagers;

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
use Moox\Address\Support\AddressRelationConfig;
use Override;

class AddressablesRelationManager extends RelationManager
{
    protected static string $relationship = 'addressables';

    #[Override]
    public static function getRelationshipName(): string
    {
        return AddressRelationConfig::relationshipName();
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) (AddressRelationConfig::addressables()['label'] ?? __('address::fields.assignments'));
    }

    public function form(Schema $schema): Schema
    {
        $ownerTypes = AddressRelationConfig::ownerTypes();
        $morphName = (string) (AddressRelationConfig::addressables()['morph_name'] ?? 'addressable');

        $fields = [
            MorphToSelect::make($morphName)
                ->label(__('address::fields.owner'))
                ->types(
                    collect($ownerTypes)
                        ->map(fn (string $label, string $class): Type => Type::make($class)->title($label))
                        ->values()
                        ->all()
                )
                ->required()
                ->visible(fn (): bool => $ownerTypes !== []),
        ];

        foreach (AddressRelationConfig::pivotColumns() as $column) {
            $fields[] = Checkbox::make($column)
                ->label(__('address::fields.'.$column));
        }

        return $schema->components($fields);
    }

    public function table(Table $table): Table
    {
        $morphName = (string) (AddressRelationConfig::addressables()['morph_name'] ?? 'addressable');

        $columns = [
            TextColumn::make("{$morphName}_type")
                ->label(__('address::fields.owner'))
                ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                ->searchable(),
            TextColumn::make("{$morphName}_id")
                ->label('ID')
                ->searchable(),
        ];

        foreach (AddressRelationConfig::pivotColumns() as $column) {
            $columns[] = IconColumn::make($column)
                ->label(__('address::fields.'.$column))
                ->boolean();
        }

        return $table
            ->columns($columns)
            ->headerActions([
                CreateAction::make()
                    ->label(__('address::fields.add_assignment'))
                    ->visible(fn (): bool => AddressRelationConfig::ownerTypes() !== []),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
