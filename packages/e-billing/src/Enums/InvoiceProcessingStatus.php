<?php

declare(strict_types=1);

namespace Moox\EBilling\Enums;

enum InvoiceProcessingStatus: string
{
    case ParserCreated = 'parser_created';
    case DbValidated = 'db_validated';
    case HumanConfirmed = 'human_confirmed';
    case Validated = 'validated';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ParserCreated => [self::DbValidated, self::Validated, self::HumanConfirmed],
            self::DbValidated => [self::HumanConfirmed, self::Validated],
            self::HumanConfirmed => [self::Validated],
            self::Validated => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::ParserCreated => __('e-billing::fields.status_parser_created'),
            self::DbValidated => __('e-billing::fields.status_db_validated'),
            self::HumanConfirmed => __('e-billing::fields.status_human_confirmed'),
            self::Validated => __('e-billing::fields.status_validated'),
        };
    }
}
