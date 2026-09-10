<?php

declare(strict_types=1);

use Moox\EBilling\Contracts\DeliveryRecipientResolverInterface;
use Moox\EBilling\Delivery\ConfigurableDeliveryRecipientResolver;
use Moox\EBilling\Tests\TestCase;

uses(TestCase::class);

test('package binds configurable delivery recipient resolver by default', function (): void {
    expect(app(DeliveryRecipientResolverInterface::class))
        ->toBeInstanceOf(ConfigurableDeliveryRecipientResolver::class);
});
