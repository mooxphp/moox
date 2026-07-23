<?php

declare(strict_types=1);

namespace Moox\EBilling\ViewModels;

final class FieldViewData
{
    public function __construct(
        public readonly string $field,
        public readonly string $label,
        public readonly ?string $btNumber,
        public readonly mixed $value,
        public readonly ?array $validation,
        public readonly ?string $hint,
    ) {
    }

    public function status(): string
    {
        $status = $this->validation['status'] ?? null;

        return is_string($status) && $status !== '' ? $status : 'parsed';
    }

    public function badgeColor(): string
    {
        return match ($this->status()) {
            'validated', 'db_validated' => 'green',
            'parsed' => 'blue',
            'needs_review' => 'yellow',
            'missing' => 'red',
            'not_applicable' => 'gray',
            default => 'gray',
        };
    }

    public function badgeLabel(): string
    {
        return match ($this->status()) {
            'validated' => __('e-billing::fields.validation_badge_validated'),
            'db_validated' => __('e-billing::fields.validation_badge_db_validated'),
            'parsed' => __('e-billing::fields.validation_badge_parsed'),
            'needs_review' => __('e-billing::fields.validation_badge_needs_review'),
            'missing' => __('e-billing::fields.validation_badge_missing'),
            'not_applicable' => __('e-billing::fields.validation_badge_not_applicable'),
            default => __('e-billing::fields.validation_badge_unknown'),
        };
    }

    public function badgeIcon(): string
    {
        return match ($this->status()) {
            'validated', 'db_validated' => 'heroicon-o-check-circle',
            'parsed' => 'heroicon-o-document-text',
            'needs_review' => 'heroicon-o-exclamation-triangle',
            'missing' => 'heroicon-o-x-circle',
            'not_applicable' => 'heroicon-o-minus-circle',
            default => 'heroicon-o-question-mark-circle',
        };
    }
}
