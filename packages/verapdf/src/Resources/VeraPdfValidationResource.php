<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Resources;

use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\VeraPdf\Models\VeraPdfValidation;
use Moox\VeraPdf\Resources\VeraPdfValidationResource\Pages\ListVeraPdfValidations;
use Moox\VeraPdf\Resources\VeraPdfValidationResource\Pages\ViewVeraPdfValidation;

final class VeraPdfValidationResource extends BaseItemResource
{
    use HasResourceRelations;

    protected static ?string $slug = 'verapdf-validations';

    protected static ?string $model = VeraPdfValidation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?int $navigationSort = 22;

    public static function getModelLabel(): string
    {
        $label = config('verapdf.resources.verapdf-validation.single');

        return is_string($label) && $label !== ''
            ? self::resolveConfigLabel($label)
            : __('verapdf::verapdf.verapdf-validation');
    }

    public static function getPluralModelLabel(): string
    {
        $label = config('verapdf.resources.verapdf-validation.plural');

        return is_string($label) && $label !== ''
            ? self::resolveConfigLabel($label)
            : __('verapdf::verapdf.verapdf-validations');
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('verapdf.navigation_group');

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

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('verapdf::fields.summary'))
                    ->schema([
                        TextEntry::make('input_path')
                            ->label(__('verapdf::fields.filename'))
                            ->state(fn (VeraPdfValidation $record): string => $record->filenameLabel()),
                        IconEntry::make('passed')
                            ->label(__('verapdf::fields.validation_passed'))
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('validated_at')
                            ->label(__('verapdf::fields.validated_at'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('verapdf::fields.validation_messages'))
                    ->schema([
                        View::make('verapdf::filament.partials.verapdf-validation-messages')
                            ->viewData(fn (VeraPdfValidation $record): array => ['record' => $record])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('verapdf::fields.validation_report'))
                    ->schema([
                        View::make('verapdf::filament.partials.verapdf-report-iframe')
                            ->viewData(fn (VeraPdfValidation $record): array => ['record' => $record])
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
                    ->label(__('verapdf::fields.result'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('verapdf::fields.result_passed')
                        : __('verapdf::fields.result_failed'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('filename')
                    ->label(__('verapdf::fields.filename'))
                    ->state(fn (VeraPdfValidation $record): string => $record->filenameLabel())
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('input_path', 'like', '%'.$search.'%');
                    })
                    ->wrap(),
                TextColumn::make('validated_at')
                    ->label(__('verapdf::fields.validated_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('validated_at', 'desc')
            ->filters([
                TernaryFilter::make('passed')
                    ->label(__('verapdf::fields.result'))
                    ->trueLabel(__('verapdf::fields.passed'))
                    ->falseLabel(__('core::core.failed'))
                    ->placeholder(__('core::core.all')),
                Filter::make('validated_at_range')
                    ->schema([
                        DatePicker::make('from')->label(__('verapdf::fields.validated_from')),
                        DatePicker::make('to')->label(__('verapdf::fields.validated_until')),
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

    public static function getPages(): array
    {
        return [
            'index' => ListVeraPdfValidations::route('/'),
            'view' => ViewVeraPdfValidation::route('/{record}'),
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
        if ($record instanceof VeraPdfValidation) {
            return $record->filenameLabel();
        }

        return parent::getRecordTitle($record);
    }
}
