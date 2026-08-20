<?php

use Moox\MailInbox\Contracts\InboxDriver;
use Moox\MsGraph\Mail\GraphInboxDriver;

it('GraphInboxDriver exists and implements InboxDriver', function () {
    expect(class_exists(GraphInboxDriver::class))->toBeTrue()
        ->and(is_a(GraphInboxDriver::class, InboxDriver::class, true))->toBeTrue();
});

it('service provider still declares no models or migrations', function () {
    $srcPath = dirname(__DIR__, 2).'/src';
    $files = glob($srcPath.'/Models/*.php');

    expect($files)->toBeEmpty()
        ->and(is_dir(dirname(__DIR__, 2).'/database/migrations'))->toBeFalse();
});
