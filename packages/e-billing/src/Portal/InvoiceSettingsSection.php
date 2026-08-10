<?php

declare(strict_types=1);

namespace Moox\EBilling\Portal;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Formats\FormatRegistry;

/**
 * Domain logic for portal invoice settings (format + visual-copy preference).
 *
 * Does not implement portal contracts or self-register — heco/portal owns registration.
 */
final class InvoiceSettingsSection
{
    public function __construct(
        private FormatRegistry $formats,
    ) {
    }

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
                        ->options(fn (): array => $this->formatOptions())
                        ->required()
                        ->live(),
                    ToggleButtons::make('send_visual_copy')
                        ->label(__('e-billing::ebilling.send_visual_copy'))
                        ->options([
                            'with_pdf' => __('e-billing::ebilling.send_visual_copy_with_pdf'),
                            'xml_only' => __('e-billing::ebilling.send_visual_copy_xml_only'),
                        ])
                        ->inline()
                        ->required()
                        ->visible(fn (Get $get): bool => $get('preferred_ebilling_format') === 'xrechnung'),
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fill(Customer $customer): array
    {
        $defaultFormat = (string) config('e-billing.default_format', 'zugferd');
        $format = is_string($customer->preferred_ebilling_format) && $customer->preferred_ebilling_format !== ''
            ? $customer->preferred_ebilling_format
            : $defaultFormat;

        $sendVisualCopy = $customer->send_visual_copy;
        if ($sendVisualCopy === null) {
            $sendVisualCopy = (bool) config('e-billing.send_visual_copy', true);
        }

        return [
            'preferred_ebilling_format' => $format,
            'send_visual_copy' => $sendVisualCopy ? 'with_pdf' : 'xml_only',
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function persist(array $state, Customer $customer): void
    {
        $format = is_string($state['preferred_ebilling_format'] ?? null)
            ? $state['preferred_ebilling_format']
            : null;

        $allowed = array_keys($this->formatOptions());
        if ($format === null || ! in_array($format, $allowed, true)) {
            $format = (string) config('e-billing.default_format', 'zugferd');
        }

        $sendVisualCopy = null;
        if ($format === 'xrechnung') {
            $copyChoice = $state['send_visual_copy'] ?? null;
            $sendVisualCopy = $copyChoice !== 'xml_only';
        }

        $customer->forceFill([
            'preferred_ebilling_format' => $format,
            'send_visual_copy' => $sendVisualCopy,
        ])->save();
    }

    /**
     * Registry labels are the UI source of truth; customer config is the generic fallback.
     *
     * @return array<string, string>
     */
    private function formatOptions(): array
    {
        $labels = $this->formats->labels();

        if ($labels !== []) {
            return $labels;
        }

        /** @var list<string> $configured */
        $configured = config('customer.preferred_ebilling_formats', []);

        return collect($configured)
            ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
            ->mapWithKeys(fn (string $id): array => [$id => $id])
            ->all();
    }
}
