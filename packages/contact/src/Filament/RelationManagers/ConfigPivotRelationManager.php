<?php

declare(strict_types=1);

namespace Moox\Contact\Filament\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;
use Moox\Contact\Support\CompanyContactRelationConfig;

/**
 * Config-driven belongsToMany pivot manager (contact.relations.companies).
 */
class ConfigPivotRelationManager extends RelationManager
{
    public bool $inverse = false;

    protected static string $relationship = 'companies';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        $component = Livewire::current();

        $inverse = $component instanceof self && $component->inverse;

        return CompanyContactRelationConfig::label($inverse);
    }

    public static function getRelationshipName(): string
    {
        $component = Livewire::current();

        if ($component instanceof self && $component->inverse) {
            return CompanyContactRelationConfig::inverseRelationshipName();
        }

        return CompanyContactRelationConfig::relationshipName();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns($this->relatedColumns())
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => $this->pivotFormFields($action)),
            ])
            ->recordActions([
                EditAction::make()
                    ->form(fn (): array => $this->pivotFormFields()),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<int, TextColumn|IconColumn> */
    protected function relatedColumns(): array
    {
        $columns = [
            TextColumn::make('display_name')
                ->label(__('contact::fields.display_name'))
                ->searchable(),
        ];

        if (in_array('role', CompanyContactRelationConfig::pivotColumns(), true)) {
            $columns[] = TextColumn::make('pivot.role')
                ->label(__('contact::fields.role'))
                ->badge();
        }

        if (in_array('is_primary', CompanyContactRelationConfig::pivotColumns(), true)) {
            $columns[] = IconColumn::make('pivot.is_primary')
                ->label(__('contact::fields.is_primary'))
                ->boolean();
        }

        return $columns;
    }

    /** @return list<Select|Toggle> */
    protected function pivotFormFields(?AttachAction $action = null): array
    {
        $fields = [];

        if (in_array('role', CompanyContactRelationConfig::pivotColumns(), true)) {
            $fields[] = Select::make('role')
                ->label(__('contact::fields.role'))
                ->options(CompanyContactRelationConfig::roleOptions())
                ->default(CompanyContactRelationConfig::roles()[0] ?? 'general')
                ->required();
        }

        if (in_array('is_primary', CompanyContactRelationConfig::pivotColumns(), true)) {
            $fields[] = Toggle::make('is_primary')
                ->label(__('contact::fields.is_primary'));
        }

        return $fields;
    }

    public function form(Schema $schema): Schema
    {
        return $schema;
    }
}
