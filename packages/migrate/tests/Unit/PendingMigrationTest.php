<?php

declare(strict_types=1);

use Moox\Migrate\Support\PendingMigration;

it('turns a laravel filename into a short label and date', function (): void {
    $item = new PendingMigration(
        name: '2026_10_06_120000_add_deliveries_to_invoice_lines_table',
        path: '/app/database/migrations/2026_10_06_120000_add_deliveries_to_invoice_lines_table.php',
        origin: 'App',
    );

    expect($item->label())->toBe('Add Deliveries To Invoice Lines')
        ->and($item->datedAt()?->toDateString())->toBe('2026-10-06');
});
