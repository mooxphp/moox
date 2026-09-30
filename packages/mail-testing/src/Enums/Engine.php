<?php

declare(strict_types=1);

namespace Moox\MailTesting\Enums;

enum Engine: string
{
    case Php = 'php';
    case Node = 'node';

    public function label(): string
    {
        return match ($this) {
            self::Php => 'PHP',
            self::Node => 'Node',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Php => 'info',
            self::Node => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $engine): array => [$engine->value => $engine->label()])
            ->all();
    }
}
