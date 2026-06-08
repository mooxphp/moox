<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\ViewModels\InvoiceViewModel;
use Moox\Invoice\Models\Invoice;

final class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'e-billing::filament.pages.view-invoice';

    /**
     * Custom Blade view only — skip {@see ViewRecord::fillForm()} which would push
     * EN16931 Party value objects into Livewire's public {@see ViewRecord::$data}.
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
                ->modalDescription(__('e-billing::fields.action_confirm_modal_description'))
                ->modalSubmitActionLabel(__('e-billing::fields.action_confirm_submit'))
                ->visible(fn (): bool => $record instanceof Invoice
                    && $document?->review_status === InvoiceProcessingStatus::DbValidated)
                ->action(function () use ($record): void {
                    if (! $record instanceof Invoice) {
                        return;
                    }

                    $confirmed = app(ConfirmInvoiceAction::class)->execute($record);

                    if ($confirmed) {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirmed_title'))
                            ->body(__('e-billing::fields.notification_confirmed_body'))
                            ->success()
                            ->send();

                        $record->load('ebillingDocument');
                    } else {
                        Notification::make()
                            ->title(__('e-billing::fields.notification_confirm_failed_title'))
                            ->body(__('e-billing::fields.notification_confirm_failed_body'))
                            ->warning()
                            ->send();
                    }
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
