<?php

declare(strict_types=1);

namespace Moox\EBilling\Portal;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Heco\Portal\Contracts\PortalSettingsSection;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Formats\FormatRegistry;
use RuntimeException;

final class InvoiceSettingsSection implements PortalSettingsSection
{
    public function __construct(
        private FormatRegistry $formats,
    ) {}

    public function id(): string
    {
        return 'invoice-settings';
    }

    public function sort(): int
    {
        return 10;
    }

    public function schema(): array
    {
        return [
            Section::make(__('e-billing::portal.section_format'))
                ->description(__('e-billing::portal.section_format_description'))
                ->icon(Heroicon::OutlinedDocumentText)
                ->schema([
                    Select::make('preferred_ebilling_format')
                        ->label(__('e-billing::portal.preferred_ebilling_format'))
                        ->options(fn (): array => $this->formats->labels())
                        ->placeholder(fn (Get $get): string => $get('preference_level') === 'customer'
                            ? __('e-billing::portal.format_placeholder_inherit')
                            : __('e-billing::portal.format_placeholder'))
                        ->nullable()
                        ->live(),
                    Toggle::make('send_visual_copy')
                        ->label(__('e-billing::portal.send_visual_copy'))
                        ->helperText(__('e-billing::portal.send_visual_copy_help'))
                        ->default(true)
                        ->visible(fn (Get $get): bool => $this->isXRechnungEffective($get)),
                ]),
        ];
    }

    public function fill(array $context): array
    {
        $company = $context['company'];
        $level = $context['preference_level'];
        $customer = $context['customer'];

        if ($level === 'customer') {
            if ($customer === null) {
                return [
                    'preferred_ebilling_format' => null,
                    'send_visual_copy' => true,
                ];
            }

            return [
                'preferred_ebilling_format' => $customer->preferred_ebilling_format,
                'send_visual_copy' => $customer->send_visual_copy ?? true,
            ];
        }

        $companyData = is_array($company->data) ? $company->data : [];

        return [
            'preferred_ebilling_format' => is_string($companyData['preferred_ebilling_format'] ?? null)
                ? $companyData['preferred_ebilling_format']
                : null,
            'send_visual_copy' => array_key_exists('send_visual_copy', $companyData)
                ? (bool) $companyData['send_visual_copy']
                : true,
        ];
    }

    public function persist(array $state, array $context): void
    {
        $company = $context['company'];
        $level = $context['preference_level'];
        $customer = $context['customer'];

        $format = is_string($state['preferred_ebilling_format'] ?? null) && $state['preferred_ebilling_format'] !== ''
            ? $state['preferred_ebilling_format']
            : null;

        $sendVisualCopy = $format === 'xrechnung'
            ? (bool) ($state['send_visual_copy'] ?? true)
            : null;

        if ($level === 'customer') {
            if (! $customer instanceof Customer) {
                throw new RuntimeException('Customer override requires a billing unit.');
            }

            $customer->preferred_ebilling_format = $format;
            $customer->send_visual_copy = $sendVisualCopy;
            $customer->save();

            return;
        }

        $companyData = is_array($company->data) ? $company->data : [];

        if ($format === null) {
            unset($companyData['preferred_ebilling_format'], $companyData['send_visual_copy']);
        } else {
            $companyData['preferred_ebilling_format'] = $format;

            if ($format === 'xrechnung') {
                $companyData['send_visual_copy'] = $sendVisualCopy ?? true;
            } else {
                unset($companyData['send_visual_copy']);
            }
        }

        $company->forceFill(['data' => $companyData === [] ? null : $companyData])->save();
    }

    private function isXRechnungEffective(Get $get): bool
    {
        $format = $get('preferred_ebilling_format');

        if (is_string($format) && $format !== '') {
            return $format === 'xrechnung';
        }

        return false;
    }
}
