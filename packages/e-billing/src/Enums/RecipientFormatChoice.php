<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

use Moox\EBilling\Data\RecipientPreferences;

/**
 * The e-invoice format a recipient picks (`ebilling_settings.preferred_ebilling_format`). One choice covers both
 * the format and, for XRechnung, whether the visual PDF copy goes along, so no contradicting pair can be stored.
 */
enum RecipientFormatChoice: string
{
    case Zugferd = 'zugferd';
    case Xrechnung = 'xrechnung';
    case XrechnungWithPdf = 'xrechnung_with_pdf';

    public function preferences(): RecipientPreferences
    {
        return match ($this) {
            self::Zugferd => new RecipientPreferences(format: 'zugferd'),
            self::Xrechnung => new RecipientPreferences(format: 'xrechnung', sendVisualCopy: false),
            self::XrechnungWithPdf => new RecipientPreferences(format: 'xrechnung', sendVisualCopy: true),
        };
    }

    /**
     * The choice matching the config defaults (`e-billing.default.format`, `e-billing.send_visual_copy`).
     */
    public static function fromDefaults(): self
    {
        if (config('e-billing.default.format') !== 'xrechnung') {
            return self::Zugferd;
        }

        return config('e-billing.send_visual_copy', true) ? self::XrechnungWithPdf : self::Xrechnung;
    }

    public function label(): string
    {
        return __('e-billing::ebilling.format_choice.'.$this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $choice) {
            $options[$choice->value] = $choice->label();
        }

        return $options;
    }
}
