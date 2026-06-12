<?php

declare(strict_types=1);

namespace Moox\Invoice\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;

final class InvoiceModels
{
    /**
     * @return class-string<Invoice>
     */
    public static function invoice(): string
    {
        return self::resolve('invoice.models.invoice', Invoice::class);
    }

    /**
     * @return class-string<InvoiceLine>
     */
    public static function invoiceLine(): string
    {
        return self::resolve('invoice.models.invoice_line', InvoiceLine::class);
    }

    /**
     * @return class-string<InvoiceAllowanceCharge>
     */
    public static function invoiceAllowanceCharge(): string
    {
        return self::resolve('invoice.models.invoice_allowance_charge', InvoiceAllowanceCharge::class);
    }

    /**
     * @param  class-string  $fallback
     * @return class-string
     */
    private static function resolve(string $configKey, string $fallback): string
    {
        $configured = function_exists('app') && app()->bound('config')
            ? app('config')->get($configKey)
            : null;

        $class = is_string($configured) && $configured !== '' ? $configured : $fallback;

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] does not exist: {$class}");
        }

        if (! is_a($class, Model::class, true)) {
            throw new InvalidArgumentException("Configured class for [{$configKey}] must extend ".Model::class.": {$class}");
        }

        return $class;
    }
}
