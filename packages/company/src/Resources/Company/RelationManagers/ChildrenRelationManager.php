<?php

declare(strict_types=1);

namespace Moox\Company\Resources\Company\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Moox\Company\Models\Company;
use Moox\Company\Resources\CompanyResource;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return (string) config('company.relations.children.label', __('company::fields.children'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->inverseRelationship('parent')
            ->columns([
                TextColumn::make('name')
                    ->label(__('company::fields.name'))
                    ->searchable(),
                TextColumn::make('company_type')
                    ->label(__('company::fields.company_type'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('company::fields.status'))
                    ->badge(),
                IconColumn::make('is_active')
                    ->label(__('company::fields.is_active'))
                    ->boolean(),
            ])
            ->headerActions([
                AssociateAction::make()
                    ->recordTitle(fn (Model $record): string => method_exists($record, 'displayLabel')
                        ? $record->displayLabel()
                        : (string) ($record->display_name ?? $record->name ?? $record->getKey()))
                    ->preloadRecordSelect(),
                CreateAction::make()
                    ->url(fn (): string => CompanyResource::getUrl('create', [
                        'parent_id' => $this->getOwnerRecord()->getKey(),
                    ])),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Company $record): string => CompanyResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
