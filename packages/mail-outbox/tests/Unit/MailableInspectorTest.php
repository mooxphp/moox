<?php

declare(strict_types=1);

use Moox\MailOutbox\Mail\OutboxTestMail;
use Moox\MailOutbox\Support\MailableInspector;
use Moox\MailOutbox\Tests\Support\EnvelopeRecipientMailable;

test('recipients are read from envelope when mailable to properties are empty', function (): void {
    $inspector = new MailableInspector;
    $mailable = new OutboxTestMail('customer@example.com', testMode: false);

    expect($inspector->recipients($mailable))->toBe(['customer@example.com']);
});

test('recipients are read from envelope to and cc addresses', function (): void {
    $inspector = new MailableInspector;
    $mailable = new EnvelopeRecipientMailable(
        toAddresses: ['to@example.com'],
        ccAddresses: ['cc@example.com'],
    );

    expect($inspector->recipients($mailable))->toEqualCanonicalizing([
        'to@example.com',
        'cc@example.com',
    ]);
});
