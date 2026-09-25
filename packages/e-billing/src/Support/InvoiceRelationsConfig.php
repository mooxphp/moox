<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

/**
 * Merges e-billing hub relation tabs into config('invoice.relations').
 */
final class InvoiceRelationsConfig
{
    /**
     * @param  array<string, mixed>|null  $relations
     */
    public static function mergeIntoInvoiceRelations(?array $relations = null): void
    {
        $relations ??= config('e-billing.invoice_relations', []);

        if (! is_array($relations) || $relations === []) {
            return;
        }

        $merged = [];

        foreach ($relations as $key => $config) {
            if (! is_string($key) || ! is_array($config)) {
                continue;
            }

            $model = $config['model'] ?? null;

            if (is_string($model) && $model !== '' && ! class_exists($model)) {
                continue;
            }

            $relatedResource = $config['related_resource'] ?? null;

            if (is_string($relatedResource) && $relatedResource !== '' && ! class_exists($relatedResource)) {
                unset($config['related_resource']);
            }

            $merged[$key] = $config;
        }

        if ($merged === []) {
            return;
        }

        $existing = config('invoice.relations', []);

        config([
            'invoice.relations' => array_replace(
                is_array($existing) ? $existing : [],
                $merged,
            ),
        ]);
    }
}
