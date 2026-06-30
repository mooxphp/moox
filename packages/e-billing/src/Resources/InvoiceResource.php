<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Resources\InvoiceResource\Pages\ListInvoices;
use Moox\EBilling\Resources\InvoiceResource\Pages\ViewInvoice;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;

final class InvoiceResource extends BaseItemResource
{
    use SingleSoftDeleteInResource;

    protected static ?string $slug = 'invoices';

    /**
     * Resolve the model from config so a host can swap in a subclass 
     * via `invoice.models.invoice`; defaults to the generic Moox\Invoice\Models\Invoice.
     * Replaces the static `$model` property so subclass casts/relations apply on the
     * Filament read/edit path.
     */
    public static function getModel(): string
    {
        return InvoiceModels::invoice();
    }

    /**
     * Matches {@see HasListPageTabs::getTableQuery()} which passes the
     * Livewire `activeTab` value. Filament also syncs that property with the `?tab=` query string.
     */
    public static function getTableQuery(?string $activeTab = null): Builder
    {
        unset($activeTab);

        return parent::getTableQuery();
    }

    protected static function applySoftDeleteQuery(Builder $query): Builder
    {
        $model = self::getModel();

        // TODO: Moox Core vendor trait hardcodes 'deleted'/'trash' for bulk action visibility.
        // When Core makes this configurable, remove the dual-check here and use the trait's mechanism.
        $deletedTabKey = (string) config('e-billing.resources.invoices.soft_delete_tab_key', 'deleted');
        $tab = request()->query('tab');
        $activeTabQuery = request()->query('activeTab');

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && ($tab === $deletedTabKey || $activeTabQuery === $deletedTabKey)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    public static function enableCreate(): bool
    {
        return false;
    }

    public static function enableEdit(): bool
    {
        return false;
    }

    public static function enableView(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    protected static function modifyEloquentQuery(Builder $query): Builder
    {
        $query = parent::modifyEloquentQuery($query);

        return $query->with([
            'ebillingDocument',
            'ebillingDocument.kositValidations' => fn ($query) => $query->orderByDesc('validated_at')->orderByDesc('id'),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::invoiceListTableColumns())
            ->recordUrl(fn (Invoice $record): string => self::getUrl('view', ['record' => $record]))
            ->defaultSort('invoice_date', 'desc')
            ->filters(self::invoiceListTableFilters())
            ->recordActions(self::invoiceListTableRecordActions())
            ->toolbarActions(self::getBulkActions());
    }

    /**
     * @return array<int, mixed>
     */
    private static function invoiceListTableColumns(): array
    {
        return [
            TextColumn::make('invoice_number')
                ->label(__('e-billing::fields.invoice_number_short'))
                ->searchable()
                ->sortable()
                ->color('primary')
                ->weight('medium')
                ->toggleable(),
            TextColumn::make('supplier_name')
                ->label(__('e-billing::fields.supplier'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->seller?->name)
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('buyer_name')
                ->label(__('e-billing::fields.recipient'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->buyer?->name)
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('country')
                ->label(InvoiceFieldLabels::label('country'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->buyer?->address?->country_code)
                ->placeholder('—')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('invoice_date')
                ->label(__('e-billing::fields.invoice_date'))
                ->sortable()
                ->toggleable()
                ->formatStateUsing(function (?string $state): string {
                    if ($state === null || $state === '') {
                        return '—';
                    }
                    try {
                        return Carbon::parse($state)->format('d.m.Y');
                    } catch (\Throwable) {
                        return $state;
                    }
                }),
            TextColumn::make('gross_total')
                ->label(__('e-billing::fields.gross_total_short'))
                ->sortable()
                ->alignment(Alignment::End)
                ->toggleable()
                ->formatStateUsing(function ($state, Invoice $record): string {
                    $num = is_numeric($state) ? (float) $state : 0.0;
                    $formatted = number_format($num, 2, ',', '.');
                    $currency = is_string($record->currency) && $record->currency !== ''
                        ? $record->currency
                        : 'EUR';
                    $suffix = $currency === 'EUR' ? ' €' : ' '.$currency;

                    return $formatted.$suffix;
                }),
            IconColumn::make('kosit_status')
                ->label(__('e-billing::fields.kosit'))
                ->getStateUsing(fn (Invoice $record): ?bool => $record->ebillingDocument?->latestKositValidation()?->passed)
                ->tooltip(function (Invoice $record): string {
                    $passed = $record->ebillingDocument?->latestKositValidation()?->passed;

                    return match ($passed) {
                        true => __('e-billing::fields.tooltip_kosit_passed'),
                        false => __('e-billing::fields.tooltip_kosit_failed'),
                        default => __('e-billing::fields.tooltip_not_validated_yet'),
                    };
                })
                ->icon(function (?bool $state): Heroicon {
                    return match ($state) {
                        true => Heroicon::OutlinedCheckCircle,
                        false => Heroicon::OutlinedXCircle,
                        default => Heroicon::OutlinedMinusCircle,
                    };
                })
                ->color(function (?bool $state): string {
                    return match ($state) {
                        true => 'success',
                        false => 'danger',
                        default => 'gray',
                    };
                })
                ->toggleable(),
            IconColumn::make('validation_status')
                ->label(__('e-billing::fields.validation'))
                ->getStateUsing(fn (Invoice $record): string => self::validationStatusKey($record))
                ->tooltip(function (Invoice $record): string {
                    return self::validationStatusTooltip($record);
                })
                ->icon(function (string $state): Heroicon {
                    return match ($state) {
                        'ok' => Heroicon::OutlinedCheckCircle,
                        'warn' => Heroicon::OutlinedExclamationTriangle,
                        default => Heroicon::OutlinedXCircle,
                    };
                })
                ->color(function (string $state): string {
                    return match ($state) {
                        'ok' => 'success',
                        'warn' => 'warning',
                        default => 'danger',
                    };
                })
                ->toggleable(),
            ViewColumn::make('validation_score')
                ->label(__('e-billing::fields.score'))
                ->tooltip(function (Invoice $record): string {
                    $score = $record->ebillingDocument?->validation_score;

                    if ($score === null) {
                        return __('e-billing::fields.tooltip_not_validated_yet');
                    }

                    return __('e-billing::fields.tooltip_validation_score', ['score' => $score]);
                })
                ->view('e-billing::components.validation-score-ring')
                ->getStateUsing(fn (Invoice $record): ?int => $record->ebillingDocument?->validation_score)
                ->toggleable(),
            TextColumn::make('review_status')
                ->label(__('e-billing::fields.status'))
                ->badge()
                ->getStateUsing(fn (Invoice $record): InvoiceProcessingStatus => self::resolveReviewStatus($record) ?? InvoiceProcessingStatus::ParserCreated)
                ->formatStateUsing(function ($state): string {
                    $enum = $state instanceof InvoiceProcessingStatus
                        ? $state
                        : InvoiceProcessingStatus::tryFrom((string) $state) ?? InvoiceProcessingStatus::ParserCreated;

                    return self::processingStatusLabel($enum);
                })
                ->color(function ($state): string {
                    $enum = $state instanceof InvoiceProcessingStatus
                        ? $state
                        : InvoiceProcessingStatus::tryFrom((string) $state) ?? InvoiceProcessingStatus::ParserCreated;

                    return self::processingStatusColor($enum);
                })
                ->toggleable(),
            TextColumn::make('created_at')
                ->label(__('e-billing::fields.created_at'))
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function invoiceListTableFilters(): array
    {
        return [
            SelectFilter::make('review_status')
                ->label(__('e-billing::fields.status'))
                ->options([
                    InvoiceProcessingStatus::ParserCreated->value => __('e-billing::fields.status_parser_created'),
                    InvoiceProcessingStatus::DbValidated->value => __('e-billing::fields.status_db_validated'),
                    InvoiceProcessingStatus::HumanConfirmed->value => __('e-billing::fields.status_human_confirmed'),
                    InvoiceProcessingStatus::Validated->value => __('e-billing::fields.status_validated'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    if (blank($value)) {
                        return $query;
                    }

                    return $query->whereHas(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->where('review_status', $value),
                    );
                }),
            TernaryFilter::make('kosit_passed')
                ->label(__('e-billing::fields.kosit_status'))
                ->trueLabel(__('e-billing::fields.filter_kosit_passed'))
                ->falseLabel(__('e-billing::fields.filter_kosit_failed'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereHas(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->whereLatestKositValidationPassed(true),
                    ),
                    false: fn (Builder $query): Builder => $query->whereHas(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->whereLatestKositValidationPassed(false),
                    ),
                    blank: fn (Builder $query): Builder => $query,
                ),
            TernaryFilter::make('needs_review')
                ->label(__('e-billing::fields.filter_needs_review'))
                ->trueLabel(__('e-billing::fields.filter_yes'))
                ->falseLabel(__('e-billing::fields.filter_no'))
                ->queries(
                    true: fn (Builder $query): Builder => $query->whereHas(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->needsHumanReview(),
                    ),
                    false: fn (Builder $query): Builder => $query->whereDoesntHave(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->needsHumanReview(),
                    ),
                    blank: fn (Builder $query): Builder => $query,
                ),
            Filter::make('invoice_date_range')
                ->label(__('e-billing::fields.invoice_date'))
                ->schema([
                    DatePicker::make('von')->label(__('e-billing::fields.filter_from'))->native(false),
                    DatePicker::make('bis')->label(__('e-billing::fields.filter_until'))->native(false),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    $von = $data['von'] ?? null;
                    $bis = $data['bis'] ?? null;

                    if (filled($von)) {
                        $query->whereDate('invoice_date', '>=', $von);
                    }
                    if (filled($bis)) {
                        $query->whereDate('invoice_date', '<=', $bis);
                    }

                    return $query;
                }),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function invoiceListTableRecordActions(): array
    {
        return [
            Action::make('open_detail')
                ->label(__('e-billing::fields.action_details'))
                ->icon(Heroicon::OutlinedEye)
                ->url(fn (Invoice $record): string => self::getUrl('view', ['record' => $record])),
            Action::make('kosit_report')
                ->label(__('e-billing::fields.action_kosit_report'))
                ->icon(Heroicon::OutlinedDocumentMagnifyingGlass)
                ->url(function (Invoice $record): ?string {
                    $validation = $record->ebillingDocument?->latestKositValidation();
                    if ($validation === null) {
                        return null;
                    }

                    $htmlPath = $validation->report_html_path;
                    if (! is_string($htmlPath) || $htmlPath === '') {
                        return null;
                    }

                    return route('kosit-validator.report.html', ['validation' => $validation->getKey()]);
                })
                ->openUrlInNewTab()
                ->visible(function (Invoice $record): bool {
                    $htmlPath = $record->ebillingDocument?->latestKositValidation()?->report_html_path;

                    return is_string($htmlPath) && $htmlPath !== '';
                }),
            ...array_filter([
                self::enableHardDelete() ? self::getHardDeleteTableAction() : null,
                self::enableRestore() ? self::getRestoreTableAction() : null,
            ]),
        ];
    }

    public static function getBulkActions(): array
    {
        return [
            ...(self::enableRestore() ? [self::getRestoreBulkAction()] : []),
            ...(self::enableDelete() ? [self::getDeleteBulkAction()] : []),
            ...(self::enableHardDelete() ? [self::getHardDeleteBulkAction()] : []),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('e-billing.resources.invoices.enabled', true);
    }

    public static function getNavigationSort(): ?int
    {
        $sort = config('e-billing.resources.invoices.navigation_sort');

        return is_int($sort) ? $sort : (is_numeric($sort) ? (int) $sort : null);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('e-billing.resources.invoices.navigation_count_badge', false)) {
            return null;
        }

        return (string) self::getModel()::query()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('e-billing.resources.invoices.label', 'trans//e-billing::ebilling.invoice'));
    }

    public static function getPluralModelLabel(): string
    {
        return self::resolveConfigLabel((string) config('e-billing.resources.invoices.plural_label', 'trans//e-billing::ebilling.invoices'));
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('e-billing.resources.invoices.navigation_group');

        return is_string($group) && $group !== '' ? self::resolveConfigLabel($group) : null;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        $icon = config('e-billing.resources.invoices.navigation_icon');

        return is_string($icon) && $icon !== '' ? $icon : Heroicon::OutlinedDocumentText;
    }

    private static function resolveConfigLabel(string $value): string
    {
        if (str_starts_with($value, 'trans//')) {
            return __(substr($value, 8));
        }

        return $value;
    }

    private static function processingStatusLabel(InvoiceProcessingStatus $state): string
    {
        return match ($state) {
            InvoiceProcessingStatus::ParserCreated => __('e-billing::fields.status_parser_created'),
            InvoiceProcessingStatus::DbValidated => __('e-billing::fields.status_db_validated_short'),
            InvoiceProcessingStatus::HumanConfirmed => __('e-billing::fields.status_human_confirmed'),
            InvoiceProcessingStatus::Validated => __('e-billing::fields.status_validated'),
        };
    }

    private static function processingStatusColor(InvoiceProcessingStatus $state): string
    {
        return match ($state) {
            InvoiceProcessingStatus::ParserCreated => 'gray',
            InvoiceProcessingStatus::DbValidated => 'info',
            InvoiceProcessingStatus::HumanConfirmed => 'warning',
            InvoiceProcessingStatus::Validated => 'success',
        };
    }

    private static function resolveReviewStatus(Invoice $record): ?InvoiceProcessingStatus
    {
        $document = $record->ebillingDocument;

        if ($document === null) {
            return null;
        }

        $status = $document->review_status;

        if ($status instanceof InvoiceProcessingStatus) {
            return $status;
        }

        $raw = $document->getAttributes()['review_status'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return InvoiceProcessingStatus::tryFrom($raw);
    }

    private static function validationStatusKey(Invoice $record): string
    {
        $document = $record->ebillingDocument;

        if ($document === null) {
            return 'bad';
        }

        $status = self::resolveReviewStatus($record);

        if ($status === InvoiceProcessingStatus::Validated) {
            return 'ok';
        }
        if ($status === InvoiceProcessingStatus::HumanConfirmed) {
            return 'ok';
        }
        if ($document->needsHumanReview()) {
            return 'warn';
        }
        if ($status === InvoiceProcessingStatus::ParserCreated && $document->isFullyValidated()) {
            return 'ok';
        }
        if ($status === InvoiceProcessingStatus::ParserCreated) {
            return 'bad';
        }
        if ($status === InvoiceProcessingStatus::DbValidated) {
            return 'ok';
        }

        return 'bad';
    }

    private static function validationStatusTooltip(Invoice $record): string
    {
        $document = $record->ebillingDocument;

        if ($document === null) {
            return __('e-billing::fields.tooltip_please_review');
        }

        $status = self::resolveReviewStatus($record);

        if ($status === InvoiceProcessingStatus::Validated) {
            return __('e-billing::fields.tooltip_all_fields_valid');
        }
        if ($status === InvoiceProcessingStatus::HumanConfirmed) {
            return __('e-billing::fields.status_human_confirmed');
        }
        if ($document->needsHumanReview()) {
            return __('e-billing::fields.tooltip_manual_review_required');
        }
        if ($status === InvoiceProcessingStatus::ParserCreated && $document->isFullyValidated()) {
            return __('e-billing::fields.tooltip_auto_validated');
        }
        if ($status === InvoiceProcessingStatus::ParserCreated) {
            return __('e-billing::fields.tooltip_validation_errors_present');
        }
        if ($status === InvoiceProcessingStatus::DbValidated) {
            return __('e-billing::fields.tooltip_reviewed_database');
        }

        return __('e-billing::fields.tooltip_please_review');
    }
}
