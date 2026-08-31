<?php

declare(strict_types=1);

use Moox\EBilling\Support\EbillingActivityAttributeLabels;
use Moox\EBilling\Tests\ContainerTestCase;

uses(ContainerTestCase::class);

test('ebilling attribute labels translate review and gateway statuses', function (): void {
    $resolver = new EbillingActivityAttributeLabels;

    expect($resolver->resolveFieldLabel('review_status'))->toBe(__('e-billing::fields.review_status'))
        ->and($resolver->resolveFieldLabel('gateway_status'))->toBe(__('e-billing::fields.gateway_status'))
        ->and($resolver->resolveValueLabel('review_status', 'db_validated'))
        ->toBe(__('e-billing::fields.status_db_validated'))
        ->and($resolver->resolveValueLabel('review_status', 'human_confirmed'))
        ->toBe(__('e-billing::fields.status_human_confirmed'))
        ->and($resolver->resolveValueLabel('gateway_status', 'generation_failed'))
        ->toBe(__('e-billing::fields.gateway_status_generation_failed'))
        ->and($resolver->resolveValueLabel('gateway_status', 'validated'))
        ->toBe(__('e-billing::fields.gateway_status_validated'))
        ->and($resolver->resolveValueLabel('review_status', 'unknown'))->toBeNull();
});
