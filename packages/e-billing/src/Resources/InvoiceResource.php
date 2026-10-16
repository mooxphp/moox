<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources;

use Carbon\Carbon;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Moox\Core\Entities\Items\Item\BaseItemResource;
use Moox\Core\Traits\InteractsWithAuditResourceRelations;
use Moox\Core\Traits\Relations\HasResourceRelations;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInResource;
use Moox\EBilling\Actions\CreateManualUploadBatchAction;
use Moox\EBilling\Actions\CreateManualUploadDocumentAction;
use Moox\EBilling\Enums\DispatchStatus;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Resources\InvoiceResource\Pages\ListInvoices;
use Moox\EBilling\Resources\InvoiceResource\Pages\ViewInvoice;
use Moox\EBilling\Resources\InvoiceResource\RelationManagers\MailSendLogsRelationManager;
use Moox\EBilling\Support\DocumentClassification;
use Moox\EBilling\Support\DocumentClassificationLabels;
use Moox\EBilling\Support\FieldValidationProfile;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;
use Moox\MailOutbox\Models\MailSendLog;
use Throwable;

class InvoiceResource extends BaseItemResource
{
    use HasResourceRelations;
    use InteractsWithAuditResourceRelations {
        HasResourceRelations::getRelations insteadof InteractsWithAuditResourceRelations;
        HasResourceRelations::getRelations as protected getConfiguredResourceRelations;
        InteractsWithAuditResourceRelations::getRelations as protected getAuditAwareRelations;
    }
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
        $query = parent::getTableQuery();

        $deletedTabKey = (string) config('e-billing.resources.'.static::resourceConfigKey().'.soft_delete_tab_key', 'deleted');

        // "all" / needs-review / deleted: every Fassung. Other tabs stay on the current one.
        $showAllVersions = in_array($activeTab, [null, 'all', 'needs_review', $deletedTabKey], true);

        if (! $showAllVersions) {
            $query->where('is_current', true);
        }

        return $query;
    }

    protected static function applySoftDeleteQuery(Builder $query): Builder
    {
        $model = self::getModel();

        // TODO: Moox Core vendor trait hardcodes 'deleted'/'trash' for bulk action visibility.
        // When Core makes this configurable, remove the dual-check here and use the trait's mechanism.
        $deletedTabKey = (string) config('e-billing.resources.'.static::resourceConfigKey().'.soft_delete_tab_key', 'deleted');
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
        $query = static::constrainToDocumentTypes($query);

        return $query->with([
            'ebillingDocument',
            'ebillingDocument.deliveryAttempts',
            'ebillingDocument.kositValidations' => fn ($query) => $query
                ->orderByDesc('validated_at')
                ->orderByDesc('id'),
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
     * Buyer/seller are JSON-cast parties, not Eloquent relations.
     *
     * @return Closure(Builder, string): Builder
     */
    private static function searchJsonPartyName(string $partyColumn): Closure
    {
        return function (Builder $query, string $search) use ($partyColumn): Builder {
            return $query->where("{$partyColumn}->name", 'like', '%'.$search.'%');
        };
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
            TextColumn::make('document_type')
                ->label(__('e-billing::fields.document_type'))
                ->badge()
                ->formatStateUsing(fn (?string $state): ?string => $state !== null
                    ? DocumentClassificationLabels::label($state)
                    : null)
                ->visible(count(static::documentTypes()) > 1)
                ->toggleable(),
            TextColumn::make('document_version')
                ->label(__('e-billing::fields.document_version'))
                ->formatStateUsing(function ($state, Invoice $record): string {
                    $label = __('e-billing::fields.document_version_label', ['version' => $state]);

                    if ($record->is_current) {
                        return $label.' · '.__('e-billing::fields.document_version_current');
                    }

                    return $label;
                })
                ->toggleable(),
            TextColumn::make('supplier_name')
                ->label(__('e-billing::fields.supplier'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->seller?->name)
                ->searchable(query: self::searchJsonPartyName('seller'))
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('buyer_name')
                ->label(__('e-billing::fields.recipient'))
                ->getStateUsing(fn (Invoice $record): ?string => $record->buyer?->name)
                ->searchable(query: self::searchJsonPartyName('buyer'))
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
                    } catch (Throwable) {
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
                ->getStateUsing(
                    fn (Invoice $record): ?bool => $record->ebillingDocument?->latestKositValidation()?->passed
                )
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
            TextColumn::make('gateway_status')
                ->label(__('e-billing::fields.gateway_status'))
                ->badge()
                ->getStateUsing(
                    fn (Invoice $record): ?EBillingAttachmentProcessingStatus => self::resolveGatewayStatus($record)
                )
                ->formatStateUsing(fn (?EBillingAttachmentProcessingStatus $state): string => $state?->label() ?? '—')
                ->color(fn (?EBillingAttachmentProcessingStatus $state): string => $state?->color() ?? 'gray')
                ->toggleable(),
            TextColumn::make('review_status')
                ->label(__('e-billing::fields.status'))
                ->badge()
                ->getStateUsing(
                    fn (Invoice $record): InvoiceProcessingStatus => self::resolveReviewStatus($record)
                        ?? InvoiceProcessingStatus::ParserCreated
                )
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
            TextColumn::make('dispatch_status')
                ->label(__('e-billing::fields.dispatch_status'))
                ->badge()
                ->getStateUsing(fn (Invoice $record): ?DispatchStatus => DispatchStatus::forDocument($record->ebillingDocument))
                ->formatStateUsing(fn (?DispatchStatus $state): string => $state?->label() ?? '—')
                ->color(fn (?DispatchStatus $state): string => $state?->color() ?? 'gray')
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
            SelectFilter::make('dispatch_status')
                ->label(__('e-billing::fields.dispatch_status'))
                ->options(collect(DispatchStatus::cases())
                    ->mapWithKeys(fn (DispatchStatus $case): array => [$case->value => $case->label()])
                    ->all())
                ->query(function (Builder $query, array $data): Builder {
                    $status = DispatchStatus::tryFrom((string) ($data['value'] ?? ''));

                    return $status === null
                        ? $query
                        : $query->whereHas('ebillingDocument', fn ($documentQuery): Builder => $status->constrain($documentQuery));
                }),
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
            SelectFilter::make('gateway_status')
                ->label(__('e-billing::fields.gateway_status'))
                ->options(collect(EBillingAttachmentProcessingStatus::cases())
                    ->mapWithKeys(
                        fn (EBillingAttachmentProcessingStatus $case): array => [$case->value => $case->label()]
                    )
                    ->all())
                ->query(function (Builder $query, array $data): Builder {
                    $value = $data['value'] ?? null;

                    if (blank($value)) {
                        return $query;
                    }

                    return $query->whereHas(
                        'ebillingDocument',
                        fn (Builder $documentQuery): Builder => $documentQuery->where('gateway_status', $value),
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

    public static function getDeleteBulkAction(): BulkAction
    {
        return parent::getDeleteBulkAction()
            ->hidden(fn ($livewire): bool => isset($livewire->activeTab)
                && in_array($livewire->activeTab, ['trash', 'deleted'], true));
    }

    public static function getBulkActions(): array
    {
        return [
            ...(self::enableRestore() ? [self::getRestoreBulkAction()] : []),
            ...(self::enableDelete() ? [self::getDeleteBulkAction()] : []),
            ...(self::enableHardDelete() ? [self::getHardDeleteBulkAction()] : []),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    /**
     * Optional mail-outbox RM (OR invoice∥document morphs) via
     * {@see getDeclaredRelations()}. Delivery / KoSIT / veraPDF tabs come from
     * config('e-billing.invoice_relations') merged into invoice.relations
     * ({@see HasResourceRelations}). Audit Activities merge separately.
     *
     * @return array<int, mixed>
     */
    protected static function getDeclaredRelations(): array
    {
        if (! class_exists(MailSendLog::class)) {
            return [];
        }

        return [MailSendLogsRelationManager::class];
    }

    /**
     * @return array<int, mixed>
     */
    public static function getRelations(): array
    {
        return array_values(array_unique([
            ...static::getConfiguredResourceRelations(),
            ...static::getAuditAwareRelations(),
        ], SORT_REGULAR));
    }

    public static function resourceConfigKey(): string
    {
        return 'invoices';
    }

    /**
     * @return list<string>
     */
    public static function documentTypes(): array
    {
        $types = config('e-billing.resources.'.static::resourceConfigKey().'.document_types', []);

        if (! is_array($types)) {
            return [];
        }

        return array_values(array_filter($types, is_string(...)));
    }

    public static function tabsConfigPath(): string
    {
        $resourcePath = 'e-billing.tabs.'.static::resourceConfigKey();

        return is_array(config($resourcePath)) ? $resourcePath : 'e-billing.tabs.invoices';
    }

    public static function constrainToDocumentTypes(Builder $query): Builder
    {
        $documentTypes = static::documentTypes();

        if ($documentTypes !== []) {
            $query->whereIn('document_type', $documentTypes);
        }

        return $query;
    }

    /**
     * Tab badge/filter queries do not go through {@see modifyEloquentQuery()};
     * they must apply the same document-type scope as the table.
     *
     * @param  list<array{field: string, operator: string, value: mixed}>  $conditions
     */
    public static function applyListTabConditions(Builder $query, array $conditions): Builder
    {
        $query = static::constrainToDocumentTypes($query);

        $isNeedsReviewTab = false;
        $isDeletedTab = false;
        $isAllTab = true;

        foreach ($conditions as $condition) {
            $value = $condition['value'];

            if ($value instanceof Closure) {
                $value = $value();
            }

            if ($condition['field'] === 'deleted_at' && in_array(SoftDeletes::class, class_uses_recursive($query->getModel()))) {
                $query = $query->withTrashed();
            }

            if ($condition['field'] === 'deleted_at' && $condition['operator'] === '!=') {
                $isDeletedTab = true;
            }

            if (in_array($condition['field'], ['review_status', 'gateway_status', 'approval_status', 'delivered'], true)) {
                $isAllTab = false;
            }

            if ($condition['field'] === 'approval_status' && $condition['operator'] === 'in') {
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('approval_status', (array) $value),
                );

                continue;
            }

            if ($condition['field'] === 'delivered') {
                $query->whereHas('ebillingDocument', fn ($documentQuery) => DispatchStatus::Sent->constrain($documentQuery));

                continue;
            }

            if ($condition['field'] === 'review_status' && $condition['operator'] === 'in') {
                $isNeedsReviewTab = true;
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('review_status', (array) $value),
                );

                continue;
            }

            if ($condition['field'] === 'gateway_status' && $condition['operator'] === 'in') {
                $query->whereHas(
                    'ebillingDocument',
                    fn ($documentQuery) => $documentQuery->whereIn('gateway_status', (array) $value),
                );

                continue;
            }

            if ($condition['operator'] === 'in') {
                $query->whereIn($condition['field'], (array) $value);
            } elseif ($condition['operator'] === 'not_in') {
                $query->whereNotIn($condition['field'], (array) $value);
            } else {
                $query->where($condition['field'], $condition['operator'], $value);
            }
        }

        // "Alle" and needs-review keep every Fassung; other status tabs stay on current only.
        if (! $isAllTab && ! $isNeedsReviewTab && ! $isDeletedTab) {
            $query->where('is_current', true);
        }

        return $query;
    }

    /**
     * @param  list<string>  $selectableTypes
     */
    private static function manualUploadRecipientEmailField(array $selectableTypes): TextInput
    {
        return TextInput::make('recipient_email')
            ->label(__('e-billing::fields.buyer_email'))
            ->helperText(__('e-billing::fields.hint_info_buyer_email'))
            ->email()
            ->maxLength(255)
            ->live(onBlur: true)
            ->required(function (Get $get) use ($selectableTypes): bool {
                if (count($selectableTypes) === 1) {
                    $documentType = $selectableTypes[0];
                } else {
                    $declared = $get('document_type');
                    $documentType = is_int($declared) || is_string($declared) ? (string) $declared : '';
                }

                return $documentType !== ''
                    && FieldValidationProfile::priority('buyer_email', $documentType) === 'must';
            });
    }

    /**
     * Declared document type for the manual upload (ADR 0011 addendum): a collapsible "which type?"
     * instruction and a choice without preselection, required unless it is only a bulk default.
     * Nothing to choose with fewer than two codes.
     *
     * @param  list<string>  $selectableTypes
     * @return list<Section|Select>
     */
    private static function manualUploadDocumentTypeComponents(array $selectableTypes, bool $required = true): array
    {
        if (count($selectableTypes) < 2) {
            return [];
        }

        return [
            Section::make(__('e-billing::fields.document_classification_help.title'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    Text::make(self::documentTypeInstructionHtml(
                        DocumentClassificationLabels::instruction($selectableTypes),
                    )),
                ]),
            self::manualUploadDocumentTypeSelect($selectableTypes)
                ->helperText(__($required
                    ? 'e-billing::fields.document_classification_help.helper'
                    : 'e-billing::fields.bulk_upload_default_helper'))
                ->required($required),
        ];
    }

    /**
     * @param  list<string>  $selectableTypes
     */
    private static function manualUploadDocumentTypeSelect(array $selectableTypes): Select
    {
        return Select::make('document_type')
            ->label(__('e-billing::fields.document_type'))
            ->options(array_intersect_key(DocumentClassificationLabels::options(), array_flip($selectableTypes)))
            ->placeholder(__('e-billing::fields.document_classification_help.placeholder'));
    }

    /**
     * One compact block per code: bold label, rule, bulleted examples. Inline styles, because the panel
     * theme does not ship utility classes for package markup; every text is escaped.
     *
     * @param  list<array{label: string, rule: ?string, examples: list<string>}>  $blocks
     */
    private static function documentTypeInstructionHtml(array $blocks): HtmlString
    {
        $html = '';

        foreach ($blocks as $index => $block) {
            $html .= $index > 0 ? '<div style="margin-top: 1rem;">' : '<div>';
            $html .= '<p style="margin: 0; font-weight: 600;">'.e($block['label']).'</p>';

            if ($block['rule'] !== null) {
                $html .= '<p style="margin: 0.25rem 0 0;">'.e($block['rule']).'</p>';
            }

            if ($block['examples'] !== []) {
                $html .= '<ul style="margin: 0.375rem 0 0; padding-inline-start: 1.25rem; list-style: disc outside;">';
                foreach ($block['examples'] as $example) {
                    $html .= '<li style="margin: 0.125rem 0 0;">'.e($example).'</li>';
                }
                $html .= '</ul>';
            }

            $html .= '</div>';
        }

        return new HtmlString($html);
    }

    /**
     * Settings both manual upload actions share, or null when the resource has no manual upload.
     *
     * @return array{config: array<string, mixed>, disk: string, directory: string, max_size_kb: int, scope: string, requires_letterhead_overlay: bool, resource: string, selectable_types: list<string>}|null
     */
    private static function manualUploadSettings(): ?array
    {
        $resourceKey = static::resourceConfigKey();
        $config = config('e-billing.resources.'.$resourceKey.'.manual_upload');

        if (! is_array($config) || ! ($config['enabled'] ?? false)) {
            return null;
        }

        return [
            'config' => $config,
            'disk' => (string) config('e-billing.manual_upload.source_disk', 'local'),
            'directory' => (string) config('e-billing.manual_upload.source_path', 'ebilling/manual-uploads/source'),
            'max_size_kb' => max(1, (int) config('e-billing.manual_upload.max_size_kb', 20480)),
            'scope' => is_string($config['scope'] ?? null) ? $config['scope'] : $resourceKey,
            'requires_letterhead_overlay' => (bool) ($config['requires_letterhead_overlay'] ?? false),
            'resource' => $resourceKey,
            'selectable_types' => DocumentClassification::selectableForUpload($resourceKey),
        ];
    }

    /**
     * The PDF field of a manual upload; each action adds how it names and counts its files.
     *
     * @param  array{disk: string, directory: string, max_size_kb: int}  $settings
     * @param  array<string, string>  $helperReplace
     */
    private static function manualUploadPdfField(string $name, string $label, string $helperKey, array $settings, array $helperReplace = []): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->helperText(__($helperKey, [
                'max_mb' => (string) max(1, (int) round($settings['max_size_kb'] / 1024)),
                ...$helperReplace,
            ]))
            ->acceptedFileTypes(['application/pdf'])
            ->maxSize($settings['max_size_kb'])
            ->disk($settings['disk'])
            ->directory($settings['directory'])
            ->required();
    }

    /**
     * Document attributes that come from the resource and the uploader, not from the file.
     *
     * @param  array{disk: string, scope: string, requires_letterhead_overlay: bool, resource: string}  $settings
     * @return array{source_pdf_disk: string, scope: string, resource: string, requires_letterhead_overlay: bool, uploader_user_id: ?string}
     */
    private static function manualUploadSharedAttributes(array $settings): array
    {
        return [
            'source_pdf_disk' => $settings['disk'],
            'scope' => $settings['scope'],
            'resource' => $settings['resource'],
            'requires_letterhead_overlay' => $settings['requires_letterhead_overlay'],
            'uploader_user_id' => Auth::id() !== null ? (string) Auth::id() : null,
        ];
    }

    public static function getManualUploadAction(): ?Action
    {
        $settings = self::manualUploadSettings();

        if ($settings === null) {
            return null;
        }

        $label = is_string($settings['config']['label'] ?? null) ? $settings['config']['label'] : __('e-billing::fields.action_manual_upload');

        return Action::make('manualUpload')
            ->label($label)
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->modalHeading($label)
            ->modalDescription(__('e-billing::fields.action_manual_upload_modal_description'))
            ->modalSubmitActionLabel(__('e-billing::fields.action_manual_upload_submit'))
            ->form([
                self::manualUploadPdfField('pdf', __('e-billing::fields.manual_upload_pdf'), 'e-billing::fields.manual_upload_pdf_helper', $settings)
                    ->storeFileNamesIn('pdf_original_filename'),
                ...self::manualUploadDocumentTypeComponents($settings['selectable_types']),
                self::manualUploadRecipientEmailField($settings['selectable_types']),
            ])
            ->action(function (array $data, $livewire) use ($settings): void {
                $path = $data['pdf'] ?? null;

                if (! is_string($path) || $path === '') {
                    return;
                }

                app(CreateManualUploadDocumentAction::class)->execute([
                    ...self::manualUploadSharedAttributes($settings),
                    'source_pdf_path' => $path,
                    'original_filename' => self::resolveUploadedOriginalFilename($data, $path),
                    'document_type' => $data['document_type'] ?? null,
                    'recipient_email' => $data['recipient_email'] ?? null,
                ]);

                Notification::make()
                    ->title(__('e-billing::fields.notification_manual_upload_success_title'))
                    ->body(__('e-billing::fields.notification_manual_upload_success_body'))
                    ->success()
                    ->send();

                self::pollIdenticalDuplicateToast($livewire);
            });
    }

    /**
     * Bulk upload (`manual_upload.bulk_enabled`): a two-step wizard. Step 1 takes the files and optional
     * batch defaults; step 2 shows one row per file, prefilled from the defaults, with the same per-file
     * rules as the single upload. Each file becomes its own document; a failing file is skipped and reported.
     */
    public static function getBulkManualUploadAction(): ?Action
    {
        $settings = self::manualUploadSettings();

        if ($settings === null || ! ($settings['config']['bulk_enabled'] ?? false)) {
            return null;
        }

        $directory = $settings['directory'];
        $maxFiles = max(1, (int) config('e-billing.manual_upload.bulk_max_files', 50));
        $selectableTypes = $settings['selectable_types'];
        $choosesType = count($selectableTypes) >= 2;

        return Action::make('bulkManualUpload')
            ->label(__('e-billing::fields.action_bulk_manual_upload'))
            ->icon(Heroicon::OutlinedRectangleStack)
            ->modalHeading(__('e-billing::fields.action_bulk_manual_upload'))
            ->modalDescription(__('e-billing::fields.action_bulk_manual_upload_modal_description'))
            ->modalSubmitActionLabel(__('e-billing::fields.action_manual_upload_submit'))
            ->modalWidth('5xl')
            ->steps([
                Step::make(__('e-billing::fields.bulk_upload_step_files'))
                    ->schema([
                        self::manualUploadPdfField(
                            'pdfs',
                            __('e-billing::fields.bulk_upload_pdfs'),
                            'e-billing::fields.bulk_upload_pdfs_helper',
                            $settings,
                            ['max_files' => (string) $maxFiles],
                        )
                            ->multiple()
                            ->maxFiles($maxFiles)
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => self::bulkStoredFileName($file->getFilename()),
                            )
                            ->storeFileNamesIn('pdf_original_filenames'),
                        ...self::manualUploadDocumentTypeComponents($selectableTypes, required: false),
                        TextInput::make('recipient_email')
                            ->label(__('e-billing::fields.buyer_email'))
                            ->helperText(__('e-billing::fields.bulk_upload_default_helper'))
                            ->email()
                            ->maxLength(255),
                        Hidden::make('applied_defaults')->dehydrated(false),
                    ])
                    ->afterValidation(function (Get $get, Set $set) use ($directory): void {
                        $defaults = [
                            'document_type' => $get('document_type'),
                            'recipient_email' => $get('recipient_email'),
                        ];

                        $set('files', self::mergeBulkUploadRows(
                            self::bulkUploadFiles($get('pdfs'), $directory),
                            (array) ($get('files') ?? []),
                            (array) ($get('applied_defaults') ?? []),
                            $defaults,
                        ));
                        $set('applied_defaults', $defaults);
                    }),
                Step::make(__('e-billing::fields.bulk_upload_step_details'))
                    ->schema([
                        Repeater::make('files')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('e-billing::fields.manual_upload_pdf')),
                                ...($choosesType ? [TableColumn::make(__('e-billing::fields.document_type'))->markAsRequired()] : []),
                                TableColumn::make(__('e-billing::fields.buyer_email')),
                            ])
                            ->schema([
                                Hidden::make('source_pdf_path'),
                                TextInput::make('filename')->disabled()->dehydrated(false),
                                ...($choosesType ? [self::manualUploadDocumentTypeSelect($selectableTypes)->required()->live()] : []),
                                self::manualUploadRecipientEmailField($selectableTypes)->helperText(null),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ])
            ->action(function (array $data, $livewire) use ($settings): void {
                $storedPaths = array_values(array_filter((array) ($data['pdfs'] ?? []), 'is_string'));
                $originalFilenames = array_filter((array) ($data['pdf_original_filenames'] ?? []), 'is_string');

                try {
                    $result = app(CreateManualUploadBatchAction::class)->execute(
                        $storedPaths,
                        $originalFilenames,
                        array_values((array) ($data['files'] ?? [])),
                        self::manualUploadSharedAttributes($settings),
                    );
                } catch (InvalidArgumentException $exception) {
                    Notification::make()
                        ->title(__('e-billing::fields.notification_bulk_upload_title'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                $body = __('e-billing::fields.notification_bulk_upload_body', [
                    'created' => (string) count($result['created']),
                    'failed' => (string) count($result['failed']),
                ]);

                foreach ($result['failed'] as $failure) {
                    $body .= "\n".$failure['filename'].': '.$failure['reason'];
                }

                $notification = Notification::make()
                    ->title(__('e-billing::fields.notification_bulk_upload_title'))
                    ->body($body);

                if ($result['failed'] === []) {
                    $notification->success();
                } else {
                    $notification->warning()->persistent();
                }

                $notification->send();

                if ($result['created'] !== []) {
                    self::pollIdenticalDuplicateToast($livewire);
                }
            });
    }

    /**
     * Stored name of a bulk-uploaded file, derived from Livewire's unique temporary file name so that step 2
     * knows each file's final path before it is stored. Contains no client-controlled characters.
     */
    public static function bulkStoredFileName(string $temporaryFilename): string
    {
        return hash('sha256', $temporaryFilename).'.pdf';
    }

    /**
     * The stored path and client name of each file uploaded in step 1.
     *
     * @return list<array{source_pdf_path: string, filename: string}>
     */
    private static function bulkUploadFiles(mixed $files, string $directory): array
    {
        $uploads = [];

        foreach ((array) $files as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $uploads[] = [
                'source_pdf_path' => rtrim($directory, '/').'/'.self::bulkStoredFileName($file->getFilename()),
                'filename' => $file->getClientOriginalName(),
            ];
        }

        return $uploads;
    }

    /**
     * Step-2 rows after leaving step 1: one row per file. A file already listed keeps its row, and a field
     * there takes the new batch default only while it still holds the previously applied one, so edits
     * survive going back. A new file gets the defaults; a removed file loses its row.
     *
     * @param  list<array{source_pdf_path: string, filename: string}>  $files
     * @param  array<array-key, mixed>  $existingRows
     * @param  array<string, mixed>  $previousDefaults
     * @param  array{document_type: mixed, recipient_email: mixed}  $defaults
     * @return array<array-key, array<string, mixed>>
     */
    public static function mergeBulkUploadRows(array $files, array $existingRows, array $previousDefaults, array $defaults): array
    {
        $existingByPath = [];

        foreach ($existingRows as $key => $row) {
            if (is_array($row) && is_string($row['source_pdf_path'] ?? null)) {
                $existingByPath[$row['source_pdf_path']] = [$key, $row];
            }
        }

        $rows = [];

        foreach ($files as $file) {
            [$key, $row] = $existingByPath[$file['source_pdf_path']] ?? [(string) Str::uuid(), null];

            if ($row === null) {
                $rows[$key] = [...$file, ...$defaults];

                continue;
            }

            foreach ($defaults as $field => $value) {
                if ((string) ($row[$field] ?? '') === (string) ($previousDefaults[$field] ?? '')) {
                    $row[$field] = $value;
                }
            }

            $rows[$key] = $row;
        }

        return $rows;
    }

    private static function pollIdenticalDuplicateToast(mixed $livewire): void
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'checkIdenticalDuplicateToast')) {
            return;
        }

        $livewire->js(<<<'JS'
            let n = 0;
            const timer = setInterval(() => {
                $wire.checkIdenticalDuplicateToast();
                if (++n >= 10) {
                    clearInterval(timer);
                }
            }, 2000);
        JS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function resolveUploadedOriginalFilename(array $data, string $storedPath): string
    {
        $names = $data['pdf_original_filename'] ?? null;

        if (is_array($names)) {
            $candidate = $names[$storedPath] ?? null;

            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        if (is_string($names) && $names !== '') {
            return $names;
        }

        return basename($storedPath);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) config('e-billing.resources.'.static::resourceConfigKey().'.enabled', true);
    }

    public static function getNavigationSort(): ?int
    {
        $sort = config('e-billing.resources.'.static::resourceConfigKey().'.navigation_sort');

        return is_int($sort) ? $sort : (is_numeric($sort) ? (int) $sort : null);
    }

    public static function getNavigationBadge(): ?string
    {
        if (! config('e-billing.resources.'.static::resourceConfigKey().'.navigation_count_badge', false)) {
            return null;
        }

        return (string) static::constrainToDocumentTypes(self::getModel()::query())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getModelLabel(): string
    {
        $default = 'trans//e-billing::ebilling.invoice';

        return self::resolveConfigLabel((string) config('e-billing.resources.'.static::resourceConfigKey().'.label', $default));
    }

    public static function getPluralModelLabel(): string
    {
        $default = 'trans//e-billing::ebilling.invoices';

        return self::resolveConfigLabel((string) config('e-billing.resources.'.static::resourceConfigKey().'.plural_label', $default));
    }

    public static function getNavigationLabel(): string
    {
        return self::getPluralModelLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        $group = config('e-billing.resources.'.static::resourceConfigKey().'.navigation_group');

        return is_string($group) && $group !== '' ? self::resolveConfigLabel($group) : null;
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        $icon = config('e-billing.resources.'.static::resourceConfigKey().'.navigation_icon');

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

    private static function resolveGatewayStatus(Invoice $record): ?EBillingAttachmentProcessingStatus
    {
        $document = $record->ebillingDocument;

        if ($document === null) {
            return null;
        }

        $status = $document->gateway_status;

        if ($status instanceof EBillingAttachmentProcessingStatus) {
            return $status;
        }

        $raw = $document->getAttributes()['gateway_status'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        return EBillingAttachmentProcessingStatus::tryFrom($raw);
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
