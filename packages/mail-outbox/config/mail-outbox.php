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
    | Resend
    |--------------------------------------------------------------------------
    |
    | Optional class allow-list for restoring mailables from resend_payload.
    | Empty (default) keeps existing behaviour (all classes permitted after
    | decrypt). Populate to restrict which mailable classes may be resent.
    |
    */
    'resend' => [
        'allowed_mailables' => [
            // App\Mail\InvoiceMail::class,
        ],
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
    | Message-ID unsupported transports
    |--------------------------------------------------------------------------
    |
    | Laravel mail transports that reject a caller-supplied RFC 5322 Message-ID
    | (for example Microsoft Graph, which only accepts custom x- headers).
    | OutboundMessagePreparer skips stamping Message-ID for these mailers.
    |
    */
    'message_id_unsupported_transports' => [
        'microsoftgraph',
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Safe test mode
    |--------------------------------------------------------------------------
    |
    | Redirects non-allowlisted recipients to a single sandbox address using
    | Laravel's mailer alwaysTo override. Allowlisted patterns are delivered
    | for real. Redirected sends are logged as suppressed — not delivered to
    | intended recipients — so domain objects must not mark delivery.
    |
    */
    'test_mode' => [
        'enabled' => (bool) env('MAIL_OUTBOX_TEST_MODE', false),
        'redirect_to' => env('MAIL_OUTBOX_TEST_MODE_REDIRECT_TO'),
        'redirect_name' => env('MAIL_OUTBOX_TEST_MODE_REDIRECT_NAME'),
        'allowlist' => [
            // '*@example.com',
        ],
        'subject_prefix' => '[TEST to %s] ',
        'warn_in_production' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Filament operator UI for the outbound send log.
    |
    */
    'resources' => [
        'send-logs' => [

            'single' => 'trans//mail-outbox::mail-outbox.send_log',
            'plural' => 'trans//mail-outbox::mail-outbox.send_logs',

            'tabs' => [
                'all' => [
                    'label' => 'trans//mail-outbox::fields.tab_all',
                    'icon' => 'gmdi-filter-list',
                    'query' => [],
                ],
                'queued' => [
                    'label' => 'trans//mail-outbox::fields.tab_queued',
                    'icon' => 'gmdi-schedule',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'queued',
                        ],
                    ],
                ],
                'sent' => [
                    'label' => 'trans//mail-outbox::fields.tab_sent',
                    'icon' => 'gmdi-check-circle',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'sent',
                        ],
                    ],
                ],
                'failed' => [
                    'label' => 'trans//mail-outbox::fields.tab_failed',
                    'icon' => 'gmdi-error',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'failed',
                        ],
                    ],
                ],
                'suppressed' => [
                    'label' => 'trans//mail-outbox::fields.tab_suppressed',
                    'icon' => 'gmdi-block',
                    'query' => [
                        [
                            'field' => 'status',
                            'operator' => '=',
                            'value' => 'suppressed',
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'navigation_group' => 'trans//mail-outbox::mail-outbox.navigation_group',

];
