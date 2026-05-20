<?php

namespace Moox\Attribute\Resources\Attribute\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use JsonException;
use Override;
    
class AttributeValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return config('attribute.resources.attribute_value.plural');
    }

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Textarea::make('value')
                        ->label(__('attribute::field.value'))
                        ->rows(8)
                        ->required()
                        ->rules(['required', 'json'])
                        ->helperText(__('attribute::attribute.json_value_helper'))
                        ->formatStateUsing(function (mixed $state): string {
                            if ($state === null || $state === '' || $state === []) {
                                return '{}';
                            }
                            if (is_array($state)) {
                                try {
                                    return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                                } catch (JsonException) {
                                    return '{}';
                                }
                            }

                            return (string) $state;
                        })
                        ->dehydrateStateUsing(function (?string $state): array {
                            if ($state === null || trim($state) === '') {
                                return [];
                            }

                            $decoded = json_decode($state, true, 512, JSON_THROW_ON_ERROR);

                            return is_array($decoded) ? $decoded : [$decoded];
                        }),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
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
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
