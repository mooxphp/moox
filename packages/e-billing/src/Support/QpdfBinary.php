<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final class QpdfBinary
{
    public static function resolve(): string
    {
        $configured = config('e-billing.qpdf.binary_path');

        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        return 'qpdf';
    }
}
