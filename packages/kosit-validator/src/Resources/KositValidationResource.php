<?php

declare(strict_types=1);

namespace Moox\KositValidator\Resources;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\KositValidator\Models\KositValidation;
use Moox\KositValidator\Resources\KositValidationResource\Pages\ListKositValidations;
use Moox\KositValidator\Resources\KositValidationResource\Pages\ViewKositValidation;
use Moox\KositValidator\Resources\KositValidationResource\RelationManagers\KositValidatablesRelationManager;
use Moox\KositValidator\Support\KositValidationMessages;

final class KositValidationResource extends BaseItemResource
{
    protected static ?string $slug = 'kosit-validations';

    protected static ?string $model = KositValidation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 21;

    public static function getModelLabel(): string
    {
        $label = config('kosit-validator.resources.kosit-validation.single');

        return is_string($label) && $label !== ''
            ? self::resolveConfigLabel($label)
            : __('kosit-validator::kosit-validator.kosit-validation');
    }

    public static function getPluralModelLabel(): string
    {
        $label = config('kosit-validator.resources.kosit-validation.plural');

        return is_string($label) && $label !== ''
            ? self::resolveConfigLabel($label)
            : __('kosit-validator::kosit-validator.kosit-validations');
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('kosit-validator.navigation_group');

        if (! is_string($group) || $group === '') {
            return null;
        }

        return self::resolveConfigLabel($group);
    }

    private static function resolveConfigLabel(string $value): string
    {
        if (str_starts_with($value, 'trans//')) {
            return __(substr($value, 8));
        }

        return $value;
    }

    public static function getTableQuery(?string $activeTab = null): Builder
    {
        unset($activeTab);

        return parent::getTableQuery();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('kosit-validator::fields.summary'))
                    ->schema([
                        TextEntry::make('input_path')
                            ->label(__('kosit-validator::fields.filename'))
                            ->state(fn (KositValidation $record): string => $record->filenameLabel()),
                        IconEntry::make('passed')
                            ->label(__('kosit-validator::fields.validation_passed'))
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('error_counts')
                            ->label(__('kosit-validator::fields.error_counts'))
                            ->state(function (KositValidation $record): string {
                                $counts = KositValidationMessages::counts($record->errors);

                                return $counts['error'].' / '.$counts['warning'].' / '.$counts['info'];
                            }),
                        TextEntry::make('validated_at')
                            ->label(__('kosit-validator::fields.validated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('kosit-validator::fields.validation_messages'))
                    ->schema([
                        View::make('kosit-validator::filament.partials.kosit-validation-messages')
                            ->viewData(fn (KositValidation $record): array => ['record' => $record])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('kosit-validator::fields.validation_report'))
                    ->schema([
                        View::make('kosit-validator::filament.partials.kosit-report-iframe')
                            ->viewData(fn (KositValidation $record): array => ['record' => $record])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('passed')
                    ->label(__('kosit-validator::fields.result'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('kosit-validator::fields.result_passed')
                        : __('kosit-validator::fields.result_failed'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable()
                    ->grow()
                    ->width('8rem'),
                TextColumn::make('filename')
                    ->label(__('kosit-validator::fields.filename'))
                    ->state(fn (KositValidation $record): string => $record->filenameLabel())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $like = '%'.$search.'%';

                        return $query->where(function (Builder $inner) use ($like, $search): void {
                            $inner->where('input_path', 'like', $like);
                            KositValidationMessages::applyErrorsTextSearch($inner, $search);
                        });
                    })
                    ->wrap()
                    ->grow(false),
                TextColumn::make('errors_count')
                    ->label(__('kosit-validator::fields.errors'))
                    ->state(fn (KositValidation $record): int => KositValidationMessages::counts($record->errors)['error'])
                    ->badge()
                    ->color('danger')
                    ->alignment(Alignment::Center)
                    ->grow(false)
                    ->width('5rem'),
                TextColumn::make('warnings_count')
                    ->label(__('kosit-validator::fields.warnings'))
                    ->state(fn (KositValidation $record): int => KositValidationMessages::counts($record->errors)['warning'])
                    ->badge()
                    ->color('warning')
                    ->alignment(Alignment::Center)
                    ->grow(false)
                    ->width('5.5rem'),
                TextColumn::make('infos_count')
                    ->label(__('kosit-validator::fields.infos'))
                    ->state(fn (KositValidation $record): int => KositValidationMessages::counts($record->errors)['info'])
                    ->badge()
                    ->color('info')
                    ->alignment(Alignment::Center)
                    ->grow(false)
                    ->width('4.5rem'),
                TextColumn::make('validated_at')
                    ->label(__('kosit-validator::fields.validated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->grow(false)
                    ->width('10.5rem'),
            ])
            ->defaultSort('validated_at', 'desc')
            ->filters([
                TernaryFilter::make('passed')
                    ->label(__('kosit-validator::fields.result'))
                    ->trueLabel(__('kosit-validator::fields.passed'))
                    ->falseLabel(__('core::core.failed'))
                    ->placeholder(__('core::core.all')),
                Filter::make('validated_at_range')
                    ->schema([
                        DatePicker::make('from')->label(__('kosit-validator::fields.validated_from')),
                        DatePicker::make('to')->label(__('kosit-validator::fields.validated_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('validated_at', '>=', $date))
                            ->when($data['to'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('validated_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            KositValidatablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKositValidations::route('/'),
            'view' => ViewKositValidation::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function enableCreate(): bool
    {
        return false;
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function enableDelete(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if ($record instanceof KositValidation) {
            return $record->filenameLabel();
        }

        return parent::getRecordTitle($record);
    }
}
