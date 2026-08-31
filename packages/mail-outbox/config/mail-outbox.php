<?php

/*
|--------------------------------------------------------------------------
| Moox Mail Outbox
|--------------------------------------------------------------------------
|
| Outbound mail send log, size guard, retry classification, and correlation
| identifiers. Uses Laravel's mailer — this package does not own a transport.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum rendered message size (bytes)
    |--------------------------------------------------------------------------
    |
    | Messages larger than this fail with MessageTooLargeException before the
    | transport is invoked. Default: 10 MiB.
    |
    */
    'max_message_bytes' => (int) env('MAIL_OUTBOX_MAX_MESSAGE_BYTES', 10 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Retry
    |--------------------------------------------------------------------------
    |
    | Transient failures (rate limit, timeout, connection) are retried with
    | backoff. Permanent failures (rejected/malformed recipient) are terminal
    | on the first attempt. When a provider supplies a retry delay, that delay
    | is honoured for the next attempt.
    |
    */
    'retry' => [
        'max_tries' => (int) env('MAIL_OUTBOX_RETRY_MAX_TRIES', 5),
        'backoff' => [60, 300, 900],
    ],

    /*
    |--------------------------------------------------------------------------
    | Correlation header
    |--------------------------------------------------------------------------
    |
    | Self-assigned correlation identifier minted at send time and stored on
    | the send log. Added as a text header on the outbound message.
    |
    */
    'correlation_header' => env('MAIL_OUTBOX_CORRELATION_HEADER', 'X-Moox-Mail-Correlation-Id'),

    /*
    |--------------------------------------------------------------------------
    | Provider message-id read-back (default)
    |--------------------------------------------------------------------------
    |
    | When true, after a successful send the package reads the provider's
    | message identifier from the sent copy and stores it as provider_reference.
    | A failed read-back never fails the send. Override per mailer below.
    |
    */
    'read_back_provider_id' => (bool) env('MAIL_OUTBOX_READ_BACK_PROVIDER_ID', false),

    /*
    |--------------------------------------------------------------------------
    | Record foreign mail
    |--------------------------------------------------------------------------
    |
    | When true, mail sent through Laravel's mailer without SendMailJob is
    | recorded via MessageSent → RecordSentMailJob. SendMailJob sends are
    | deduplicated by correlation id or message id. Set false to disable.
    |
    */
    'record_foreign_mail' => (bool) env('MAIL_OUTBOX_RECORD_FOREIGN_MAIL', true),

    /*
    |--------------------------------------------------------------------------
    | Per-mailer overrides
    |--------------------------------------------------------------------------
    |
    | Example:
    | 'mailers' => [
    |     'docs' => ['read_back_provider_id' => true],
    |     'bulk' => ['read_back_provider_id' => false],
    | ],
    |
    */
    'mailers' => [
        //
    ],

];
