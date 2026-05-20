<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use JsonException;
use Override;

class ProductAttributeValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributeValues';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) config('product.relations.attribute_values.label');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('attribute.name')
                    ->label(__('core::core.name'))
                    ->sortable(),
                TextColumn::make('value')
                    ->label(__('attribute::field.value'))
                    ->formatStateUsing(function (mixed $state): string {
                        if ($state === null || $state === [] || $state === '') {
                            return '';
                        }
                        if (is_array($state)) {
                            try {
                                return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                            } catch (JsonException) {
                                return '';
                            }
                        }

                        return (string) $state;
                    })
                    ->limit(80),
            ])
            ->headerActions([
                AttachAction::make(),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }
}
