<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

/**
 * Gateway-specific `ebilling_documents.gateway_status` values (string column).
 */
enum EBillingAttachmentProcessingStatus: string
{
    case Generating = 'generating';
    case GenerationFailed = 'generation_failed';
    case Validating = 'validating';
    case Validated = 'validated';
    case ValidationFailed = 'validation_failed';
    case ValidatorError = 'validator_error';
    case IgnoredForeign = 'ignored_foreign';

    /**
     * User-facing label for Filament / tables.
     * Only safe to call in request context (uses __()).
     */
    public function label(): string
    {
        return match ($this) {
            self::Generating => __('e-billing::fields.gateway_status_generating'),
            self::GenerationFailed => __('e-billing::fields.gateway_status_generation_failed'),
            self::Validating => __('e-billing::fields.gateway_status_validating'),
            self::Validated => __('e-billing::fields.gateway_status_validated'),
            self::ValidationFailed => __('e-billing::fields.gateway_status_validation_failed'),
            self::ValidatorError => __('e-billing::fields.gateway_status_validator_error'),
            self::IgnoredForeign => __('e-billing::fields.gateway_status_ignored_foreign'),
        };
    }

    /**
     * Filament badge color token.
     */
    public function color(): string
    {
        return match ($this) {
            self::Generating => 'info',
            self::GenerationFailed => 'danger',
            self::Validating => 'info',
            self::Validated => 'success',
            self::ValidationFailed => 'danger',
            self::ValidatorError => 'warning',
            self::IgnoredForeign => 'gray',
        };
    }

    public function isFailure(): bool
    {
        return in_array($this, [
            self::GenerationFailed,
            self::ValidationFailed,
            self::ValidatorError,
        ], true);
    }

    /**
     * Terminal states: pipeline will not progress further for this attachment.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Generating,
            self::Validating => false,
            self::GenerationFailed,
            self::Validated,
            self::ValidationFailed,
            self::ValidatorError,
            self::IgnoredForeign => true,
        };
    }

    /**
     * Successful completion: validated artifact is ready for delivery.
     */
    public function isSuccessfulTerminal(): bool
    {
        return $this === self::Validated;
    }
}
