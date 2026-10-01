<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\SlideOverPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Actions\ApproveDocumentAction;
use Moox\EBilling\Actions\ClassifyDocumentTypeAction;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Actions\CorrectFieldValueAction;
use Moox\EBilling\Actions\LeaveEditAction;
use Moox\EBilling\Actions\QueueDocumentDeliveryAction;
use Moox\EBilling\Actions\RejectDocumentAction;
use Moox\EBilling\Actions\RematchAttributionAction;
use Moox\EBilling\Actions\RestoreRejectedDocumentAction;
use Moox\EBilling\Actions\SetInvoiceAttributionAction;
use Moox\EBilling\Actions\SetRecipientEmailAction;
use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Approval\DocumentDispatchGuard;
use Moox\EBilling\Data\SelectiveRedispatchPlan;
use Moox\EBilling\Delivery\SelectiveRedispatchPlanner;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\Support\CrossCheckedFields;
use Moox\EBilling\Support\DocumentClassificationLabels;
use Moox\EBilling\Support\InvoiceFieldLabels;
use Moox\EBilling\Support\ReviewEditField;
use Moox\EBilling\Support\ReviewFieldCatalog;
use Moox\EBilling\ViewModels\InvoiceViewModel;
use Moox\EBilling\ViewModels\ReviewWorkspace;
use Moox\Invoice\Models\Invoice;
use Throwable;

/**
 * @property-read InvoiceViewModel $invoiceViewModel
 * @property-read ReviewWorkspace $reviewWorkspace
 */
class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'e-billing::filament.pages.view-invoice';

    /**
     * Mixed review workspace (ADR 0004): per-field corrections and customer attribution;
     * leaving it re-runs matching.
     */
    public bool $reviewEditing = false;

    /**
     * Custom Blade view only — skip {@see ViewRecord::fillForm()} which would push
     * EN16931 Party value objects into Livewire's public {@see ViewRecord::$data}.
     *
     * Delivery attempts, KoSIT/veraPDF validations, optional mail-outbox logs, and
     * Activity use Filament's native relation-manager slot via {@see content()}
     * (rendered as {{ $this->content }} in the custom Blade view).
     */
    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getRelationManagersContentComponent(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset(
            $data['seller'],
            $data['buyer'],
            $data['delivery'],
            $data['payment_means'],
        );

        return $data;
    }

    #[Computed]
    public function invoiceViewModel(): InvoiceViewModel
    {
        $record = $this->getRecord();
        assert($record instanceof Invoice);

        return new InvoiceViewModel($record, $record->ebillingDocument);
    }

    #[Computed]
    public function reviewWorkspace(): ReviewWorkspace
    {
        $record = $this->getRecord();
        assert($record instanceof Invoice);

        return new ReviewWorkspace($record, $this->reviewDocument(), app(ReviewFieldCatalog::class));
    }

    /**
     * Pencil on a row of the review workspace; the row key comes in as the `key` argument.
     */
    public function editFieldAction(): Action
    {
        return Action::make('editField')
            ->iconButton()
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->tooltip(__('e-billing::fields.review_edit_field'))
            ->visible(fn (array $arguments): bool => $this->reviewEditing
                && $this->reviewWorkspace->isAvailable()
                && $this->reviewEdit($arguments)->isEditable())
            ->slideOver()
            ->slideOverPosition(SlideOverPosition::Start)
            ->modalHeading(fn (array $arguments): string => $this->reviewEdit($arguments)->label)
            ->modalDescription(__('e-billing::fields.review_edit_description'))
            ->modalSubmitActionLabel(__('e-billing::fields.review_edit_submit'))
            ->fillForm(fn (array $arguments): array => $this->reviewFormFill($this->reviewEdit($arguments)))
            ->schema(fn (array $arguments): array => $this->reviewFormSchema($this->reviewEdit($arguments)))
            ->action(fn (array $data, array $arguments) => $this->saveReviewEdit($this->reviewEdit($arguments), $data));
    }

    /**
     * Customer attribution inside the review workspace; replaces the former set-attribution action.
     */
    public function editAttributionAction(): Action
    {
        return Action::make('editAttribution')
            ->link()
            ->label(__('e-billing::fields.review_change_customer'))
            ->icon(Heroicon::OutlinedUserCircle)
            ->visible(fn (): bool => $this->reviewEditing && $this->reviewWorkspace->isAvailable())
            ->modalHeading(__('e-billing::fields.review_change_customer'))
            ->modalDescription(__('e-billing::fields.review_change_customer_description'))
            ->fillForm(fn (): array => ['customer_id' => $this->reviewDocument()?->customer_id])
            ->schema([
                Select::make('customer_id')
                    ->label(__('e-billing::fields.field_customer'))
                    ->searchable()
                    ->nullable()
                    ->native(false)
                    ->getSearchResultsUsing(fn (string $search): array => Customer::query()
                        ->where(fn ($query) => $query->where('customer_name', 'like', "%{$search}%")
                            ->orWhere('customer_number', 'like', "%{$search}%"))
                        ->orderBy('customer_name')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Customer $customer): array => [
                            (string) $customer->getKey() => ReviewWorkspace::customerOptionLabel($customer),
                        ])
                        ->all())
                    ->getOptionLabelUsing(function (?string $value): ?string {
                        $customer = CrossCheckedFields::findCustomer($value);

                        return $customer instanceof Customer ? ReviewWorkspace::customerOptionLabel($customer) : $value;
                    }),
            ])
            ->action(function (array $data): void {
                $document = $this->reviewDocument();
                if (! $document instanceof EbillingDocument) {
                    return;
                }

                $customerId = $data['customer_id'] ?? null;
                app(SetInvoiceAttributionAction::class)->execute(
                    $document,
                    is_string($customerId) && $customerId !== '' ? $customerId : null,
                );

                Notification::make()
                    ->title(__('e-billing::fields.notification_attribution_updated_title'))
                    ->body(__('e-billing::fields.notification_attribution_updated_body'))
                    ->success()
                    ->send();

                $this->refreshReviewState();
            });
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        $record = $this->record;
        assert($record instanceof Invoice);

        $document = $record->ebillingDocument;
        $vm = new InvoiceViewModel($record, $document);
        $attention = $vm->attentionFieldCount();

        return [
            Action::make('start_review_edit')
                ->label(__('e-billing::fields.action_start_review_edit'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->visible(fn (): bool => ! $this->reviewEditing && $this->reviewWorkspace->isAvailable())
                ->action(function (): void {
                    $this->reviewEditing = true;
                }),
            Action::make('finish_review_edit')
                ->label(__('e-billing::fields.action_finish_review_edit'))
                ->icon(Heroicon::OutlinedCheck)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('e-billing::fields.action_finish_review_edit_modal_heading'))
                ->modalDescription(__('e-billing::fields.action_finish_review_edit_modal_description'))
                ->modalSubmitActionLabel(__('e-billing::fields.action_finish_review_edit_submit'))
                ->visible(fn (): bool => $this->reviewEditing)
                ->action(fn () => $this->leaveReviewEdit()),
            Action::make('confirm')
                ->label($attention > 0
                    ? __('e-billing::fields.action_confirm_with_attention', ['count' => $attention])
                    : __('e-billing::fields.action_confirm'))
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('e-billing::fields.action_confirm_modal_heading'))
                ->modalDescription(function () use ($record): string {
                    $otherVersions = $record->versionFamily()
                        ->reject(fn (Invoice $invoice): bool => (string) $invoice->getKey() === (string) $record->getKey())
                        ->count();

                    if ($otherVersions > 0) {
                        return __('e-billing::fields.action_confirm_modal_description_with_versions', [
                            'count' => $otherVersions,
                            'version' => $record->document_version,
                        ]);
                    }

                    return __('e-billing::fields.action_confirm_modal_description');
                })
                ->modalSubmitActionLabel(__('e-billing::fields.action_confirm_submit'))
                ->visible(fn (): bool => ! $this->reviewEditing
                    && $document?->review_status === InvoiceProcessingStatus::DbValidated)
                ->action(function () use ($record): void {
                    if (! $record instanceof Invoice) {
                        return;
                    }

                    $result = app(ConfirmInvoiceAction::class)->execute($record);

                    if ($result['confirmed']) {
                        $body = $result['previous_current_count'] > 0
                            ? __('e-billing::fields.notification_confirmed_body_with_versions', [
                                'count' => $result['previous_current_count'],
                                'version' => $record->fresh()?->document_version ?? $record->document_version,
                            ])
                            : __('e-billing::fields.notification_confirmed_body');

                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirmed_title'))
                            ->body($body)
                            ->success()
                            ->send();

                        $record->refresh();
                        $record->load('ebillingDocument');
                    } else {
                        $missingMust = $result['missing_must_fields'];
                        $body = $missingMust !== []
                            ? __('e-billing::fields.notification_confirm_failed_missing_must_body', [
                                'fields' => implode(', ', array_map(
                                    static fn (string $field): string => InvoiceFieldLabels::label($field),
                                    $missingMust,
                                )),
                            ])
                            : __('e-billing::fields.notification_confirm_failed_body');

                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->body($body)
                            ->warning()
                            ->send();
                    }
                }),
            Action::make('approve_dispatch')
                ->label(__('e-billing::fields.action_approve_dispatch'))
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('e-billing::fields.action_approve_dispatch_modal_heading'))
                ->modalDescription(__('e-billing::fields.action_approve_dispatch_modal_description'))
                ->visible(fn (): bool => ! $this->reviewEditing
                    && (bool) config('e-billing.approval.required', true)
                    && $document instanceof EbillingDocument
                    && app(DocumentApprovalGuard::class)->canApprove($document))
                ->action(function () use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    $this->runHeaderAction(
                        $record,
                        fn () => app(ApproveDocumentAction::class)->execute($document),
                        'e-billing::fields.notification_approval_success_title',
                        'e-billing::fields.notification_approval_success_body',
                        'e-billing::fields.notification_approval_failed_title',
                        'e-billing::fields.notification_approval_failed_body',
                    );
                }),
            Action::make('reject_dispatch')
                ->label(__('e-billing::fields.action_reject_dispatch'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->modalHeading(__('e-billing::fields.action_reject_dispatch_modal_heading'))
                ->schema([
                    Textarea::make('reason')
                        ->label(__('e-billing::fields.action_reject_reason'))
                        ->required(),
                ])
                ->visible(fn (): bool => (bool) config('e-billing.approval.required', true)
                    && $document instanceof EbillingDocument
                    && app(DocumentApprovalGuard::class)->canReject($document))
                ->action(function (array $data) use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    $reason = is_string($data['reason'] ?? null) ? $data['reason'] : '';

                    $this->runHeaderAction(
                        $record,
                        fn () => app(RejectDocumentAction::class)->execute($document, $reason),
                        'e-billing::fields.notification_reject_success_title',
                        'e-billing::fields.notification_reject_success_body',
                        'e-billing::fields.notification_approval_failed_title',
                        'e-billing::fields.notification_approval_failed_body',
                    );

                    $this->reviewEditing = false;
                }),
            Action::make('restore_approval')
                ->label(__('e-billing::fields.action_restore_approval'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->modalHeading(__('e-billing::fields.action_restore_approval_modal_heading'))
                ->schema([
                    Textarea::make('reason')
                        ->label(__('e-billing::fields.action_reject_reason'))
                        ->required(),
                ])
                ->visible(fn (): bool => (bool) config('e-billing.approval.required', true)
                    && $document instanceof EbillingDocument
                    && app(DocumentApprovalGuard::class)->canRestore($document))
                ->action(function (array $data) use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    $reason = is_string($data['reason'] ?? null) ? $data['reason'] : '';

                    $this->runHeaderAction(
                        $record,
                        fn () => app(RestoreRejectedDocumentAction::class)->execute($document, $reason),
                        'e-billing::fields.notification_restore_success_title',
                        'e-billing::fields.notification_restore_success_body',
                        'e-billing::fields.notification_approval_failed_title',
                        'e-billing::fields.notification_approval_failed_body',
                    );
                }),
            Action::make('redispatch_delivery')
                ->label(__('e-billing::fields.action_redispatch_delivery'))
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('gray')
                ->modalHeading(__('e-billing::fields.action_redispatch_delivery_modal_heading'))
                ->modalDescription(__('e-billing::fields.action_redispatch_delivery_modal_description'))
                ->visible(fn (): bool => (bool) config('e-billing.delivery.enabled', false)
                    && $document instanceof EbillingDocument
                    && app(DocumentDispatchGuard::class)->isDispatchable($document))
                ->fillForm(function () use ($document): array {
                    if (! $document instanceof EbillingDocument) {
                        return ['channels' => []];
                    }

                    return ['channels' => $this->selectiveRedispatchPlan($document)->defaultSelectedKeys];
                })
                ->schema(function () use ($document): array {
                    if (! $document instanceof EbillingDocument) {
                        return [];
                    }

                    $plan = $this->selectiveRedispatchPlan($document);

                    return [
                        CheckboxList::make('channels')
                            ->label(__('e-billing::fields.redispatch_channels'))
                            ->options($plan->labels)
                            ->descriptions($plan->hints)
                            ->required()
                            ->minItems(1)
                            ->live(),
                        Placeholder::make('redispatch_success_warning')
                            ->hiddenLabel()
                            ->content(fn (Get $get): HtmlString => new HtmlString(
                                '<div class="text-sm text-warning-600 dark:text-warning-400">'
                                .implode('<br>', array_map(
                                    static fn (string $line): string => e($line),
                                    $this->redispatchWarningLines($plan, $this->selectedRedispatchChannels($get('channels'))),
                                ))
                                .'</div>'
                            ))
                            ->visible(fn (Get $get): bool => $plan->warningKeys(
                                $this->selectedRedispatchChannels($get('channels')),
                            ) !== []),
                    ];
                })
                ->action(function (array $data) use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    app(QueueDocumentDeliveryAction::class)->execute(
                        $document->fresh() ?? $document,
                        $this->selectedRedispatchChannels($data['channels'] ?? []),
                    );

                    Notification::make()
                        ->title(__('e-billing::fields.notification_redispatch_success_title'))
                        ->body(__('e-billing::fields.notification_redispatch_success_body'))
                        ->success()
                        ->send();

                    $record->load('ebillingDocument');
                }),
        ];
    }

    /**
     * Leave-edit is the single trigger for re-matching, regeneration and re-validation (ADR 0004,
     * mooxphp/e-billing#48); the status banner shows the queued pipeline until it ends. When it cannot
     * start, the reviewer stays in edit mode.
     */
    private function leaveReviewEdit(): void
    {
        $this->reviewEditing = false;
        $document = $this->reviewDocument();

        if ($document instanceof EbillingDocument && $this->reviewWorkspace->isAvailable()) {
            try {
                $started = app(LeaveEditAction::class)->execute($document);
                $this->reviewEditing = false;

                Notification::make()
                    ->title(__('e-billing::fields.notification_review_finished_title'))
                    ->body(__($started
                        ? 'e-billing::fields.notification_review_finished_body'
                        : 'e-billing::fields.notification_review_finished_unchanged_body'))
                    ->success()
                    ->send();
            } catch (Throwable $exception) {
                report($exception);

                Notification::make()
                    ->title(__('e-billing::fields.notification_review_finish_failed_title'))
                    ->body(__('e-billing::fields.notification_review_finish_failed_body'))
                    ->danger()
                    ->send();
            }
        } else {
            $this->reviewEditing = false;
        }

        $this->refreshReviewState();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function reviewEdit(array $arguments): ReviewEditField
    {
        return $this->reviewWorkspace->edit((string) ($arguments['key'] ?? ''));
    }

    /**
     * Form state keys are `input_{n}` in the order of the row's inputs; dots in attributes would nest.
     *
     * @return array<string, mixed>
     */
    private function reviewFormFill(ReviewEditField $edit): array
    {
        if ($edit->target === null) {
            return [];
        }

        $state = [];
        foreach (array_keys($edit->inputs) as $index => $attribute) {
            $value = ReviewWorkspace::currentValue($edit->target, $attribute);
            $state["input_{$index}"] = is_scalar($value) ? (string) $value : null;
        }

        return $state;
    }

    /**
     * @return list<Component>
     */
    private function reviewFormSchema(ReviewEditField $edit): array
    {
        if ($edit->kind === ReviewEditField::KIND_CLASSIFICATION) {
            return [
                Select::make('input_0')
                    ->label($edit->label)
                    ->options(DocumentClassificationLabels::options())
                    ->helperText(fn (Get $get): ?string => DocumentClassificationLabels::hint((string) $get('input_0')))
                    ->live()
                    ->required()
                    ->native(false),
            ];
        }

        $document = $this->reviewDocument();
        $strict = $edit->crossChecked !== null && CrossCheckedFields::isStrict();
        $addressOptions = $edit->crossChecked !== null && CrossCheckedFields::isAddressField($edit->crossChecked)
            ? CrossCheckedFields::addressOptions($edit->crossChecked, $document)
            : null;

        $components = [];

        if ($addressOptions !== null) {
            $inputIndexes = array_flip(array_keys($edit->inputs));
            $components[] = Select::make('master_address')
                ->label(__('e-billing::fields.review_master_data_address'))
                ->helperText($strict
                    ? __('e-billing::fields.review_master_data_address_strict_hint')
                    : __('e-billing::fields.review_master_data_address_hint'))
                ->options(array_map(static fn (array $option): string => $option['label'], $addressOptions))
                ->searchable()
                ->native(false)
                ->live()
                ->afterStateUpdated(function (?string $state, Set $set) use ($addressOptions, $inputIndexes): void {
                    foreach ($addressOptions[$state]['parts'] ?? [] as $part => $value) {
                        foreach ($inputIndexes as $attribute => $index) {
                            if (str_ends_with($attribute, ".address.{$part}")) {
                                $set("input_{$index}", $value);
                            }
                        }
                    }
                });
        }

        foreach (array_keys($edit->inputs) as $index => $attribute) {
            $name = "input_{$index}";
            $label = $this->reviewWorkspace->inputLabel($edit, $attribute);
            $type = $edit->inputs[$attribute];
            $isAddressPart = $addressOptions !== null && str_contains($attribute, '.address.');

            if ($edit->crossChecked !== null && $addressOptions === null) {
                $options = CrossCheckedFields::options($edit->crossChecked, $document);
                $components[] = $strict
                    ? Select::make($name)->label($label)->options($options)->searchable()->native(false)
                    : TextInput::make($name)->label($label)->datalist(array_values($options))
                        ->helperText($options === [] ? __('e-billing::fields.review_master_data_none') : __('e-billing::fields.review_master_data_hint'));

                continue;
            }

            $component = match ($type) {
                'textarea' => Textarea::make($name)->rows(3),
                'date' => DatePicker::make($name),
                'decimal' => TextInput::make($name)->numeric(),
                'integer' => TextInput::make($name)->integer(),
                'email' => TextInput::make($name)->email(),
                default => TextInput::make($name),
            };

            if ($edit->kind === ReviewEditField::KIND_RECIPIENT && $document?->inboxToEmail() !== null) {
                $component->helperText(__('e-billing::fields.review_recipient_inbox_hint', ['email' => $document->inboxToEmail()]));
            }

            $components[] = $component->label($label)->disabled($strict && $isAddressPart);
        }

        $components[] = Textarea::make('note')
            ->label(__('e-billing::fields.review_note'))
            ->helperText(__('e-billing::fields.review_note_hint'))
            ->rows(2);

        return $components;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveReviewEdit(ReviewEditField $edit, array $data): void
    {
        $document = $this->reviewDocument();
        if (! $document instanceof EbillingDocument || ! $edit->isEditable() || $edit->target === null) {
            return;
        }

        $note = filled($data['note'] ?? null) ? trim((string) $data['note']) : null;

        try {
            $changed = match ($edit->kind) {
                ReviewEditField::KIND_CLASSIFICATION => app(ClassifyDocumentTypeAction::class)->execute($document, (string) ($data['input_0'] ?? '')),
                ReviewEditField::KIND_RECIPIENT => app(SetRecipientEmailAction::class)->execute($document, $data['input_0'] ?? null, $note),
                default => app(CorrectFieldValueAction::class)->executeMany($document, $edit->target, $this->reviewValues($edit, $data), $note) > 0,
            };
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title(__('e-billing::fields.notification_review_save_failed_title'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__('e-billing::fields.notification_review_save_failed_title'))
                ->body(__('e-billing::fields.notification_review_save_failed_body'))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title($changed
                ? __('e-billing::fields.notification_review_saved_title')
                : __('e-billing::fields.notification_review_unchanged_title'))
            ->success()
            ->send();

        $this->refreshReviewState();
    }

    /**
     * Attribute => submitted value. In strict mode the parts of a cross-checked address come from the
     * chosen master-data address only; choosing none clears them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function reviewValues(ReviewEditField $edit, array $data): array
    {
        $strictAddress = $edit->crossChecked !== null
            && CrossCheckedFields::isStrict()
            && CrossCheckedFields::isAddressField($edit->crossChecked);
        $parts = $strictAddress
            ? CrossCheckedFields::addressOptions($edit->crossChecked, $this->reviewDocument())[(string) ($data['master_address'] ?? '')]['parts'] ?? []
            : null;

        $values = [];
        foreach (array_keys($edit->inputs) as $index => $attribute) {
            $values[$attribute] = $parts !== null && str_contains($attribute, '.address.')
                ? ($parts[Str::afterLast($attribute, '.')] ?? null)
                : ($data["input_{$index}"] ?? null);
        }

        return $values;
    }

    /**
     * Read fresh: an approval or attribution may have landed since the page was rendered.
     */
    private function reviewDocument(): ?EbillingDocument
    {
        return EbillingDocument::query()->where('invoice_id', $this->getRecord()->getKey())->first();
    }

    private function refreshReviewState(): void
    {
        $this->record = $this->resolveRecord($this->getRecord()->getKey());
        unset($this->invoiceViewModel, $this->reviewWorkspace);
    }

    private function selectiveRedispatchPlan(EbillingDocument $document): SelectiveRedispatchPlan
    {
        $fresh = $document->fresh() ?? $document;
        $fresh->loadMissing('deliveryAttempts');

        $planner = app(SelectiveRedispatchPlanner::class);

        return $planner->plan(
            $planner->configuredChannelKeys(),
            $fresh->deliveryAttempts,
        );
    }

    /**
     * @return list<string>
     */
    private function selectedRedispatchChannels(mixed $value): array
    {
        return array_values(array_filter(
            (array) $value,
            static fn (mixed $key): bool => is_string($key) && $key !== '',
        ));
    }

    /**
     * @param  list<string>  $selected
     * @return list<string>
     */
    private function redispatchWarningLines(SelectiveRedispatchPlan $plan, array $selected): array
    {
        $lines = [];

        foreach ($plan->warningKeys($selected) as $key) {
            $lines[] = __('e-billing::fields.redispatch_success_warning_line', [
                'channel' => $plan->labels[$key] ?? $key,
            ]);
        }

        return $lines;
    }

    private function runHeaderAction(
        Invoice $record,
        callable $action,
        string $successTitleKey,
        string $successBodyKey,
        string $failedTitleKey,
        string $failedBodyKey,
    ): void {
        try {
            $action();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title(__($failedTitleKey))
                ->body(__($failedBodyKey))
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__($successTitleKey))
            ->body(__($successBodyKey))
            ->success()
            ->send();

        $record->load('ebillingDocument');
    }

    protected function resolveRecord(int|string $key): Model
    {
        return self::getResource()::getEloquentQuery()
            ->with(['lines', 'lines.allowanceCharges', 'allowanceCharges', 'ebillingDocument.deliveryAttempts'])
            ->whereKey($key)
            ->firstOrFail();
    }
}
