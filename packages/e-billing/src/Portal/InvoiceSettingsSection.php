<?php

declare(strict_types=1);

namespace Moox\EBilling\Portal;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Moox\EBilling\Enums\RecipientFormatChoice;
use Moox\EBilling\Models\EbillingSetting;

/**
 * Domain logic for portal invoice settings: the recipient's format choice, stored in `ebilling_settings` on the
 * model the portal passes as owner (the buyer's contact).
 *
 * Does not implement portal contracts or self-register — the portal owns registration.
 */
final class InvoiceSettingsSection
{
    public function id(): string
    {
        return 'invoice-settings';
    }

    public function sort(): int
    {
        return 10;
    }

    /**
     * @return array<int, mixed>
     */
    public function schema(): array
    {
        return [
            Section::make(__('e-billing::ebilling.invoice_settings'))
                ->description(__('e-billing::ebilling.invoice_settings_description'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    Select::make('preferred_ebilling_format')
                        ->label(__('e-billing::ebilling.preferred_ebilling_format'))
                        ->options(RecipientFormatChoice::options())
                        ->required(),
                ]),
        ];
    }

    /**
     * @return array{preferred_ebilling_format: string}
     */
    public function fill(Model $owner): array
    {
        $choice = EbillingSetting::for($owner)->preferred_ebilling_format ?? RecipientFormatChoice::fromDefaults();

        return ['preferred_ebilling_format' => $choice->value];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function persist(array $state, Model $owner): void
    {
        $choice = RecipientFormatChoice::from((string) ($state['preferred_ebilling_format'] ?? ''));

        EbillingSetting::query()->updateOrCreate(
            ['owner_type' => $owner->getMorphClass(), 'owner_id' => (string) $owner->getKey()],
            ['preferred_ebilling_format' => $choice],
        );
    }
}
