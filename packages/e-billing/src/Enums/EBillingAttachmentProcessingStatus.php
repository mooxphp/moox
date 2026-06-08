<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

/**
 * Gateway-specific `ebilling_documents.gateway_status` values (string column).
 */
enum EBillingAttachmentProcessingStatus: string
{
    case XmlGenerating = 'xml_generating';
    case XmlGenerationFailed = 'xml_generation_failed';
    case XmlValidated = 'xml_validated';
    case XmlValidationFailed = 'xml_validation_failed';
    case KositError = 'kosit_error';
    case ZugferdPdfGenerating = 'zugferd_pdf_generating';
    case ZugferdPdfGenerated = 'zugferd_pdf_generated';
    case ZugferdPdfFailed = 'zugferd_pdf_failed';
    case IgnoredForeign = 'ignored_foreign';

    /**
     * User-facing label for Filament / tables.
     * Only safe to call in request context (uses __()).
     */
    public function label(): string
    {
        return match ($this) {
            self::XmlGenerating => __('e-billing::fields.gateway_status_xml_generating'),
            self::XmlGenerationFailed => __('e-billing::fields.gateway_status_xml_generation_failed'),
            self::XmlValidated => __('e-billing::fields.gateway_status_xml_validated'),
            self::XmlValidationFailed => __('e-billing::fields.gateway_status_xml_validation_failed'),
            self::KositError => __('e-billing::fields.gateway_status_kosit_error'),
            self::ZugferdPdfGenerating => __('e-billing::fields.gateway_status_zugferd_pdf_generating'),
            self::ZugferdPdfGenerated => __('e-billing::fields.gateway_status_zugferd_pdf_generated'),
            self::ZugferdPdfFailed => __('e-billing::fields.gateway_status_zugferd_pdf_failed'),
            self::IgnoredForeign => __('e-billing::fields.gateway_status_ignored_foreign'),
        };
    }

    /**
     * Filament badge color token.
     */
    public function color(): string
    {
        return match ($this) {
            self::XmlGenerating => 'info',
            self::XmlGenerationFailed => 'danger',
            self::XmlValidated => 'success',
            self::XmlValidationFailed => 'danger',
            self::KositError => 'warning',
            self::ZugferdPdfGenerating => 'info',
            self::ZugferdPdfGenerated => 'success',
            self::ZugferdPdfFailed => 'danger',
            self::IgnoredForeign => 'gray',
        };
    }

    /**
     * Terminal states: pipeline will not progress further for this attachment.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::XmlGenerating,
            self::XmlValidated,
            self::ZugferdPdfGenerating => false,
            self::XmlGenerationFailed,
            self::XmlValidationFailed,
            self::KositError,
            self::ZugferdPdfGenerated,
            self::ZugferdPdfFailed,
            self::IgnoredForeign => true,
        };
    }

    /**
     * Successful completion: ZUGFeRD PDF was produced for this attachment.
     */
    public function isSuccessfulTerminal(): bool
    {
        return $this === self::ZugferdPdfGenerated;
    }
}
