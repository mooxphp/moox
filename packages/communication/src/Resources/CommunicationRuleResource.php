<?php

declare(strict_types=1);

namespace Moox\Communication\Resources;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Moox\Communication\Models\CommunicationRule;
use Moox\Communication\Resources\CommunicationRule\Pages\CreateCommunicationRule;
use Moox\Communication\Resources\CommunicationRule\Pages\EditCommunicationRule;
use Moox\Communication\Resources\CommunicationRule\Pages\ListCommunicationRules;
use Moox\Communication\Resources\CommunicationRule\Pages\ViewCommunicationRule;
use Moox\Support\Active\HasActiveForm;
use Moox\Support\Audit\HasAuditForm;
use Moox\Support\Filament\FeatureFields;
use Moox\Support\Identity\HasIdentityForm;
use Moox\Support\Relation\HasRelationForm;

class CommunicationRuleResource extends Resource
{
    use HasActiveForm;
    use HasAuditForm;
    use HasIdentityForm;
    use HasRelationForm;

    protected static ?string $model = CommunicationRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'gmdi-rule';

    public static function getModelLabel(): string
    {
        return __('communication::communication.communication_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('communication::communication.communication_rules');
    }

    public static function getNavigationLabel(): string
    {
        return __('communication::communication.communication_rules');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('communicationType.translations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Grid::make(1)
                        ->schema([
                            static::activeSection(),
                            static::domainSection(),
                        ])
                        ->columnSpan(2),
                    Grid::make(1)
                        ->schema([
                            static::relationSection()->columns(1),
                            static::identitySection()->columns(1),
                            static::auditSection()->columns(1),
                        ])
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    protected static function domainSection(): Section
    {
        $translation = static::featureTranslation();

        return Section::make(FeatureFields::label($translation, 'domain'))
            ->schema([
                Select::make('owner_type')
                    ->label(FeatureFields::label($translation, 'owner_type'))
                    ->options(static::ownerTypeOptions())
                    ->searchable()
                    ->required()
                    ->rules(static::featureRules('owner_type')),
            ])
            ->columns(1);
    }

    /**
     * @return array<string, string>
     */
    protected static function ownerTypeOptions(): array
    {
        /** @var array<class-string, array{label?: string}> $ownerTypes */
        $ownerTypes = config('communication.owner_types', []);
        $options = [];

        foreach ($ownerTypes as $class => $definition) {
            if (! is_string($class) || $class === '') {
                continue;
            }

            $label = is_array($definition) && is_string($definition['label'] ?? null)
                ? $definition['label']
                : class_basename($class);

            $options[$class] = $label;
        }

        return $options;
    }

    protected static function featureTranslation(): string
    {
        return 'communication::fields';
    }

    /**
     * @return list<string|ValidationRule>
     */
    protected static function featureRules(string $field): array
    {
        return match ($field) {
            'is_active' => ['boolean'],
            'owner_type' => ['required', 'string', 'max:255'],
            'communication_type_id' => ['required', 'integer'],
            default => [],
        };
    }

    public static function table(Table $table): Table
    {
        $translation = static::featureTranslation();
        $ownerTypeOptions = static::ownerTypeOptions();

        return $table
            ->columns([
                TextColumn::make('owner_type')
                    ->label(FeatureFields::label($translation, 'owner_type'))
                    ->formatStateUsing(fn (?string $state): string => $ownerTypeOptions[$state] ?? (string) $state)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('communicationType.title')
                    ->label(FeatureFields::label($translation, 'communication_type_id'))
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(FeatureFields::label($translation, 'updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('owner_type')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(FeatureFields::label($translation, 'is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunicationRules::route('/'),
            'create' => CreateCommunicationRule::route('/create'),
            'view' => ViewCommunicationRule::route('/{record}'),
            'edit' => EditCommunicationRule::route('/{record}/edit'),
        ];
    }
}
