<?php

declare(strict_types=1);

use Moox\MailOutbox\Exceptions\PermanentMailFailureException;
use Moox\MailOutbox\Exceptions\TransientMailFailureException;
use Moox\MailOutbox\Support\FailureKind;
use Moox\MailOutbox\Support\MailFailureClassifier;
use Moox\MailOutbox\Tests\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;

uses(TestCase::class);

test('classifies explicit transient and permanent marker exceptions', function (): void {
    $classifier = new MailFailureClassifier;

    $transient = $classifier->classify(new TransientMailFailureException('rate limit', retryAfterSeconds: 90));
    $permanent = $classifier->classify(new PermanentMailFailureException('bad recipient'));

    expect($transient->kind)->toBe(FailureKind::Transient)
        ->and($transient->retryAfterSeconds)->toBe(90)
        ->and($permanent->kind)->toBe(FailureKind::Permanent);
});

test('classifies connection timeouts as transient and rejected recipients as permanent', function (): void {
    $classifier = new MailFailureClassifier;

    $timeout = $classifier->classify(new TransportException('Connection timed out'));
    $rejected = $classifier->classify(new TransportException('550 recipient rejected'));

    expect($timeout->kind)->toBe(FailureKind::Transient)
        ->and($rejected->kind)->toBe(FailureKind::Permanent);
});

test('classifies http 429 as transient and honours retry-after in the message', function (): void {
    $classifier = new MailFailureClassifier;

    $rateLimited = $classifier->classify(new UnexpectedResponseException('429 Too Many Requests retry-after: 45', 429));

    expect($rateLimited->kind)->toBe(FailureKind::Transient)
        ->and($rateLimited->retryAfterSeconds)->toBe(45);
});

test('classifies http 4xx as permanent and 5xx as transient', function (): void {
    $classifier = new MailFailureClassifier;

    expect($classifier->classify(new UnexpectedResponseException('400 Bad Request', 400))->kind)
        ->toBe(FailureKind::Permanent)
        ->and($classifier->classify(new UnexpectedResponseException('503 Unavailable', 503))->kind)
        ->toBe(FailureKind::Transient);
});
