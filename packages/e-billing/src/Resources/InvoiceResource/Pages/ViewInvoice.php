<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Actions\ApproveDocumentAction;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Actions\RejectDocumentAction;
use Moox\EBilling\Actions\RematchAttributionAction;
use Moox\EBilling\Actions\RestoreRejectedDocumentAction;
use Moox\EBilling\Actions\SetInvoiceAttributionAction;
use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Enums\DocumentApprovalStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\ViewModels\InvoiceViewModel;
use Moox\Invoice\Models\Invoice;
use Throwable;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'e-billing::filament.pages.view-invoice';

    /**
     * Custom Blade view only — skip {@see ViewRecord::fillForm()} which would push
     * EN16931 Party value objects into Livewire's public {@see ViewRecord::$data}.
     *
     * The Activity relation manager is embedded directly in the Blade view, because
     * this custom layout does not use Filament's default content schema tabs.
     */
    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();
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
                ->visible(fn (): bool => $record instanceof Invoice
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
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->body(__('e-billing::fields.notification_confirm_failed_body'))
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
                ->visible(fn (): bool => (bool) config('e-billing.approval.required', true)
                    && $document instanceof EbillingDocument
                    && app(DocumentApprovalGuard::class)->canApprove($document))
                ->action(function () use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    try {
                        app(ApproveDocumentAction::class)->execute($document);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('e-billing::fields.notification_approval_success_title'))
                        ->body(__('e-billing::fields.notification_approval_success_body'))
                        ->success()
                        ->send();

                    $record->load('ebillingDocument');
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

                    try {
                        app(RejectDocumentAction::class)->execute($document, $reason);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('e-billing::fields.notification_reject_success_title'))
                        ->body(__('e-billing::fields.notification_reject_success_body'))
                        ->success()
                        ->send();

                    $record->load('ebillingDocument');
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
                    && $document->resolveApprovalStatusEnum() === DocumentApprovalStatus::Rejected)
                ->action(function (array $data) use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    $reason = is_string($data['reason'] ?? null) ? $data['reason'] : '';

                    try {
                        app(RestoreRejectedDocumentAction::class)->execute($document, $reason);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('e-billing::fields.notification_restore_success_title'))
                        ->body(__('e-billing::fields.notification_restore_success_body'))
                        ->success()
                        ->send();

                    $record->load('ebillingDocument');
                }),
            Action::make('set_attribution')
                ->label(__('e-billing::fields.action_set_attribution'))
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('gray')
                ->modalHeading(__('e-billing::fields.action_set_attribution_modal_heading'))
                ->modalDescription(__('e-billing::fields.action_set_attribution_modal_description'))
                ->modalSubmitActionLabel(__('e-billing::fields.action_set_attribution_submit'))
                ->visible(fn (): bool => $document instanceof EbillingDocument)
                ->fillForm(fn (): array => [
                    'customer_id' => $document?->customer_id,
                ])
                ->schema([
                    Select::make('customer_id')
                        ->label(__('e-billing::fields.field_customer'))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->getSearchResultsUsing(function (string $search): array {
                            return Customer::query()
                                ->where(function ($query) use ($search): void {
                                    $query->where('customer_name', 'like', "%{$search}%")
                                        ->orWhere('customer_number', 'like', "%{$search}%");
                                })
                                ->orderBy('customer_name')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Customer $customer): array => [
                                    (string) $customer->getKey() => $customer->displayLabel()
                                        .(filled($customer->customer_number) ? " ({$customer->customer_number})" : ''),
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(function (?string $value): ?string {
                            if ($value === null || $value === '') {
                                return null;
                            }

                            $customer = Customer::query()->withTrashed()->find($value);

                            return $customer instanceof Customer
                                ? $customer->displayLabel()
                                    .(filled($customer->customer_number) ? " ({$customer->customer_number})" : '')
                                : $value;
                        }),
                ])
                ->action(function (array $data) use ($record, $document): void {
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

                    $record->load('ebillingDocument');
                }),
            Action::make('rematch')
                ->label(__('e-billing::fields.action_rematch'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('e-billing::fields.action_rematch_modal_heading'))
                ->modalDescription(__('e-billing::fields.action_rematch_modal_description'))
                ->modalSubmitActionLabel(__('e-billing::fields.action_rematch_submit'))
                ->visible(fn (): bool => $document instanceof EbillingDocument)
                ->action(function () use ($record, $document): void {
                    if (! $document instanceof EbillingDocument) {
                        return;
                    }

                    try {
                        app(RematchAttributionAction::class)->execute($document->fresh() ?? $document);
                    } catch (Throwable) {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_rematch_failed_title'))
                            ->body(__('e-billing::fields.notification_rematch_failed_body'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('e-billing::fields.notification_rematch_success_title'))
                        ->body(__('e-billing::fields.notification_rematch_success_body'))
                        ->success()
                        ->send();

                    $record->load('ebillingDocument');
                }),
        ];
    }

    protected function resolveRecord(int|string $key): Model
    {
        return self::getResource()::getEloquentQuery()
            ->with(['lines', 'lines.allowanceCharges', 'allowanceCharges', 'ebillingDocument'])
            ->whereKey($key)
            ->firstOrFail();
    }
}
