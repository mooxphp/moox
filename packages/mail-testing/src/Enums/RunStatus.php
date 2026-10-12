<?php

declare(strict_types=1);

namespace Moox\MailTesting\Enums;

enum RunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Running => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Running => 'heroicon-m-arrow-path',
            self::Completed => 'heroicon-m-check-circle',
            self::Failed => 'heroicon-m-x-circle',
        };
    }

    public function label(): string
    {
        return __('mail-testing::translations.status_'.$this->value);
    }
}
