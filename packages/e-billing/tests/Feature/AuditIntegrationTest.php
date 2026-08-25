<?php

declare(strict_types=1);

use Moox\Audit\Filament\RelationManagers\ActivitiesRelationManager;
use Moox\Audit\Models\Activity;
use Moox\Audit\Support\AuditBootstrap;
use Moox\EBilling\EBillingServiceProvider;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\InvoiceResource;
use Moox\EBilling\Tests\TestCase;
use Moox\Invoice\Support\InvoiceModels;

uses(TestCase::class);

beforeEach(function (): void {
    if (! class_exists(AuditBootstrap::class) || ! class_exists(Activity::class)) {
        $this->markTestSkipped('moox/audit is not installed');
    }

    config([
        'audit.enabled' => true,
        'audit.activity_model' => Activity::class,
        'activitylog.enabled' => true,
        'activitylog.activity_model' => Activity::class,
    ]);

    AuditBootstrap::clear();

    $provider = app()->getProvider(EBillingServiceProvider::class);

    if ($provider instanceof EBillingServiceProvider) {
        $provider->packageBooted();
    }

    AuditBootstrap::boot();
});

test('invoice and document creates are written to the activity log', function (): void {
    $invoiceClass = InvoiceModels::invoice();
    $invoice = $invoiceClass::factory()->create([
        'invoice_number' => 'AUDIT-1001',
    ]);

    $document = EbillingDocument::query()->create([
        'invoice_id' => $invoice->getKey(),
        'format' => 'zugferd',
        'gateway_status' => 'generating',
        'review_status' => 'parser_created',
        'scope' => 'default',
    ]);

    $document->update([
        'gateway_status' => 'validating',
        'customer_id' => null,
    ]);

    expect(Activity::query()
        ->where('subject_type', $document->getMorphClass())
        ->where('subject_id', $document->getKey())
        ->where('event', 'updated')
        ->count())->toBe(0);

    $document->update([
        'gateway_status' => 'validated',
    ]);

    expect(Activity::query()
        ->where('subject_type', $invoice->getMorphClass())
        ->where('subject_id', $invoice->getKey())
        ->where('event', 'created')
        ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('subject_type', $document->getMorphClass())
            ->where('subject_id', $document->getKey())
            ->where('event', 'created')
            ->exists())->toBeTrue()
        ->and(Activity::query()
            ->where('subject_type', $document->getMorphClass())
            ->where('subject_id', $document->getKey())
            ->where('event', 'updated')
            ->count())->toBe(1)
        ->and(Activity::query()
            ->where('subject_type', $document->getMorphClass())
            ->where('subject_id', $document->getKey())
            ->where('event', 'updated')
            ->value('attribute_changes'))
        ->toMatchArray([
            'attributes' => ['gateway_status' => 'validated'],
            'old' => ['gateway_status' => 'validating'],
        ]);
});

test('invoice resource exposes the audit activities relation manager', function (): void {
    expect(InvoiceResource::getRelations())
        ->toContain(ActivitiesRelationManager::class);
});
