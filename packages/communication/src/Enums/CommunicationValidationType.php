<?php

declare(strict_types=1);

namespace Moox\Communication\Enums;

enum CommunicationValidationType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Fax = 'fax';
    case Url = 'url';

    public function usesCallingCode(): bool
    {
        return $this === self::Phone || $this === self::Fax;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
