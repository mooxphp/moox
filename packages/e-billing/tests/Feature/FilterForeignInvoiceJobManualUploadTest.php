<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Moox\EBilling\Actions\CreateManualUploadDocumentAction;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Jobs\FilterForeignInvoiceJob;
use Moox\EBilling\Jobs\GenerateArtifactJob;
use Moox\EBilling\Jobs\StoreBillDataJob;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Models\UploadedPdfSource;
use Moox\EBilling\Services\InboxMessagePipelineFinalizer;
use Moox\EBilling\Tests\Support\InvoiceFixtures;
use Moox\EBilling\Tests\Support\PipelineFixtures;
use Moox\EBilling\Tests\TestCase;
use Moox\MailInbox\Services\GraphMailService;

uses(TestCase::class);

test('manual upload filter job skips graph and dispatches generate', function (): void {
    config([
        'mail-inbox.graph.tenant_id' => '',
        'mail-inbox.graph.client_id' => '',
        'mail-inbox.graph.client_secret' => '',
    ]);

    Bus::fake();

    $source = UploadedPdfSource::query()->create([
        'source_pdf_disk' => 'local',
        'source_pdf_path' => 'ebilling/manual-uploads/source/test.pdf',
        'original_filename' => 'gutschrift.pdf',
        'scope' => 'credit-notes',
        'requires_letterhead_overlay' => true,
    ]);

    $document = EbillingDocument::query()->create([
        'source_type' => $source->getMorphClass(),
        'source_id' => $source->getKey(),
        'scope' => 'credit-notes',
        'bill_data' => InvoiceFixtures::minimal('Gutschrift', '381')->toArray(),
        'review_status' => InvoiceProcessingStatus::ParserCreated,
    ]);

    (new FilterForeignInvoiceJob($document->getKey()))->handle();

    expect($document->fresh()->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Generating);

    Bus::assertDispatched(
        GenerateArtifactJob::class,
        fn (GenerateArtifactJob $job): bool => $job->ebillingDocumentId === $document->getKey(),
    );
});

test('pipeline finalizer can be resolved without graph credentials', function (): void {
    config([
        'mail-inbox.graph.tenant_id' => '',
        'mail-inbox.graph.client_id' => '',
        'mail-inbox.graph.client_secret' => '',
    ]);

    expect(app(InboxMessagePipelineFinalizer::class))->toBeInstanceOf(InboxMessagePipelineFinalizer::class);
});

test('mailbox domestic invoices still generate without calling graph', function (): void {
    $graph = Mockery::mock(GraphMailService::class);
    $graph->shouldNotReceive('moveMessageToFolderByName');
    app()->instance(GraphMailService::class, $graph);

    Bus::fake();

    $fixture = PipelineFixtures::arrangeInvoice($this);
    $fixture->document->update(['gateway_status' => null]);

    (new FilterForeignInvoiceJob($fixture->document->getKey()))->handle();

    expect($fixture->document->fresh()->gateway_status)->toBe(EBillingAttachmentProcessingStatus::Generating);

    Bus::assertDispatched(GenerateArtifactJob::class);
});

test('mailbox foreign invoices still move via graph and skip generation', function (): void {
    $graph = Mockery::mock(GraphMailService::class);
    $graph->shouldReceive('moveMessageToFolderByName')->once();
    app()->instance(GraphMailService::class, $graph);

    Bus::fake();

    $fixture = PipelineFixtures::arrangeInvoice($this);
    $billData = $fixture->document->bill_data;
    $billData['billing_country'] = 'FR';
    $billData['customer_address']['country'] = 'FR';
    $fixture->document->update([
        'bill_data' => $billData,
        'gateway_status' => null,
    ]);

    (new FilterForeignInvoiceJob($fixture->document->getKey()))->handle();

    expect($fixture->document->fresh()->gateway_status)->toBe(EBillingAttachmentProcessingStatus::IgnoredForeign);

    Bus::assertNotDispatched(GenerateArtifactJob::class);
});

test('manual upload rejects path traversal and foreign disks', function (): void {
    Bus::fake();

    $action = app(CreateManualUploadDocumentAction::class);

    expect(fn () => $action->execute([
        'source_pdf_path' => 'ebilling/manual-uploads/source/../../.env',
        'source_pdf_disk' => 'local',
    ]))->toThrow(InvalidArgumentException::class);

    expect(fn () => $action->execute([
        'source_pdf_path' => 'ebilling/manual-uploads/source/ok.pdf',
        'source_pdf_disk' => 's3',
    ]))->toThrow(InvalidArgumentException::class);

    Bus::assertNothingDispatched();
});

test('manual upload persists a path inside the configured directory', function (): void {
    Bus::fake([StoreBillDataJob::class]);

    $document = app(CreateManualUploadDocumentAction::class)->execute([
        'source_pdf_path' => 'ebilling/manual-uploads/source/01KZX.pdf',
        'source_pdf_disk' => 'local',
        'original_filename' => 'gutschrift.pdf',
        'scope' => 'credit-notes',
    ]);

    expect($document->sourceStoragePath())->toBe('ebilling/manual-uploads/source/01KZX.pdf');

    Bus::assertDispatched(StoreBillDataJob::class);
});
