<?php

declare(strict_types=1);

namespace Moox\MailTesting\Enums;

enum FillMode: string
{
    case Demo = 'demo';
    case Random = 'random';

    public function label(): string
    {
        return match ($this) {
            self::Demo => __('mail-testing::translations.fill_demo'),
            self::Random => __('mail-testing::translations.fill_random'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode): array => [$mode->value => $mode->label()])
            ->all();
    }
}
