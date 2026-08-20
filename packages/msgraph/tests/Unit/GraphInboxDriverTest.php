<?php

declare(strict_types=1);

use Moox\MailInbox\Enums\SettlementOutcome;
use Moox\MailInbox\InboxMessageDto;
use Moox\Msgraph\Exceptions\GraphException;
use Moox\Msgraph\Tests\Support\GraphHttp;
use Psr\Http\Message\RequestInterface;

require_once dirname(__DIR__).'/Support/GraphHttp.php';

function graphQuery(RequestInterface $request): array
{
    parse_str($request->getUri()->getQuery(), $query);

    return $query;
}

function requestBody(RequestInterface $request): array
{
    $body = $request->getBody();
    if ($body->isSeekable()) {
        $body->rewind();
    }

    $decoded = json_decode($body->getContents(), true);
    if (! is_array($decoded)) {
        return [];
    }

    if (isset($decoded['DestinationId']) && ! isset($decoded['destinationId'])) {
        $decoded['destinationId'] = $decoded['DestinationId'];
    }

    if (isset($decoded['displayName']) || isset($decoded['DisplayName'])) {
        $decoded['displayName'] = $decoded['displayName'] ?? $decoded['DisplayName'];
    }

    return $decoded;
}

function deltaMessage(
    string $id,
    string $subject = 'Hello',
    string $from = 'sender@example.com',
    bool $hasAttachments = false,
    bool $removed = false,
): array {
    $row = [
        'id' => $id,
        'internetMessageId' => '<'.$id.'@example.com>',
        'subject' => $subject,
        'from' => [
            'emailAddress' => [
                'address' => $from,
                'name' => 'Sender',
            ],
        ],
        'receivedDateTime' => '2026-01-15T10:00:00Z',
        'body' => [
            'contentType' => 'html',
            'content' => '<p>Hi</p>',
        ],
        'hasAttachments' => $hasAttachments,
    ];

    if ($removed) {
        $row['@removed'] = ['reason' => 'deleted'];
    }

    return $row;
}

beforeEach(function () {
    config()->set('msgraph.mail', [
        'folders' => [
            'processing' => 'ClaimHold',
            'processed' => 'DoneBox',
            'failed' => 'DeadLetter',
            'ignored' => 'SkipBin',
        ],
        'page_size' => 10,
        'delta_max_pages_per_poll' => 1,
    ]);
});

it('fetches the first delta page with page size, select, immutable ids, and a nextLink cursor', function () {
    $history = [];
    $nextLink = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$skiptoken=PAGE2';

    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.nextLink' => $nextLink,
            'value' => [
                deltaMessage('msg-1', 'Invoice', 'from@example.com'),
            ],
        ]),
    ]), $history);

    $page = $driver->fetch();

    expect($page->messages)->toHaveCount(1)
        ->and($page->messages[0])->toBeInstanceOf(InboxMessageDto::class)
        ->and($page->messages[0]->externalId)->toBe('msg-1')
        ->and($page->messages[0]->subject)->toBe('Invoice')
        ->and($page->messages[0]->from)->toBe('from@example.com')
        ->and($page->messages[0]->bodyHtml)->toBe('<p>Hi</p>')
        ->and($page->continuationCursor)->toBe($nextLink)
        ->and($page->resumeCursor)->toBeNull();

    $graph = GraphHttp::graphRequests($history);
    expect($graph)->toHaveCount(1);

    $request = $graph[0]['request'];
    $uri = (string) $request->getUri();
    $query = graphQuery($request);

    expect($uri)->toContain('/mailFolders/inbox/messages/delta')
        ->and($request->getMethod())->toBe('GET')
        ->and($request->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"')
        ->and((int) ($query['$top'] ?? $query['top'] ?? 0))->toBe(10)
        ->and($query['$select'] ?? $query['select'] ?? '')->toContain('id')
        ->and($query['$select'] ?? $query['select'] ?? '')->toContain('subject')
        ->and($query['$select'] ?? $query['select'] ?? '')->toContain('hasAttachments');
});

it('resumes from the returned cursor without walking remaining pages', function () {
    $history = [];
    $nextLink = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$skiptoken=PAGE2';
    $deltaLink = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$deltatoken=FINAL';

    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.nextLink' => $nextLink,
            'value' => [deltaMessage('msg-1')],
        ]),
        GraphHttp::json(200, [
            '@odata.deltaLink' => $deltaLink,
            'value' => [deltaMessage('msg-2')],
        ]),
    ]), $history);

    $first = $driver->fetch();
    $second = $driver->fetch($first->continuationCursor);

    expect($first->continuationCursor)->toBe($nextLink)
        ->and($first->resumeCursor)->toBeNull()
        ->and($second->messages)->toHaveCount(1)
        ->and($second->messages[0]->externalId)->toBe('msg-2')
        ->and($second->continuationCursor)->toBeNull()
        ->and($second->resumeCursor)->toBe($deltaLink);

    $graph = GraphHttp::graphRequests($history);
    expect($graph)->toHaveCount(2);

    $resumeUri = (string) $graph[1]['request']->getUri();
    expect($resumeUri)->toContain('skiptoken=PAGE2')
        ->and($graph[1]['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');

    $resumeQuery = graphQuery($graph[1]['request']);
    expect($resumeQuery)->not->toHaveKey('$top')
        ->and($resumeQuery)->not->toHaveKey('top');
});

it('uses the deltaLink as resumeCursor on the last page', function () {
    $history = [];
    $deltaLink = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$deltatoken=FINAL';

    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.deltaLink' => $deltaLink,
            'value' => [deltaMessage('msg-last')],
        ]),
    ]), $history);

    $page = $driver->fetch();

    expect($page->continuationCursor)->toBeNull()
        ->and($page->resumeCursor)->toBe($deltaLink)
        ->and($page->messages[0]->externalId)->toBe('msg-last');
});

it('drops @removed delta placeholders', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.deltaLink' => 'https://graph.microsoft.com/v1.0/delta-final',
            'value' => [
                deltaMessage('msg-gone', removed: true),
                deltaMessage('msg-keep'),
            ],
        ]),
    ]), $history);

    $page = $driver->fetch();

    expect($page->messages)->toHaveCount(1)
        ->and($page->messages[0]->externalId)->toBe('msg-keep');
});

it('includes file attachment metadata on fetch and skips non-file attachments', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.deltaLink' => 'https://graph.microsoft.com/v1.0/delta-final',
            'value' => [deltaMessage('msg-1', hasAttachments: true)],
        ]),
        GraphHttp::json(200, [
            'value' => [
                [
                    '@odata.type' => '#microsoft.graph.fileAttachment',
                    'id' => 'att-file',
                    'name' => 'doc.pdf',
                    'contentType' => 'application/pdf',
                    'size' => 1234,
                ],
                [
                    '@odata.type' => '#microsoft.graph.itemAttachment',
                    'id' => 'att-item',
                    'name' => 'embedded',
                ],
            ],
        ]),
    ]), $history);

    $page = $driver->fetch();

    expect($page->messages[0]->attachments)->toBe([
        [
            'id' => 'att-file',
            'name' => 'doc.pdf',
            'content_type' => 'application/pdf',
            'size' => 1234,
        ],
    ]);
});

it('honours page_size on the initial delta request', function () {
    config()->set('msgraph.mail.page_size', 7);

    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.deltaLink' => 'https://graph.microsoft.com/v1.0/delta-final',
            'value' => [],
        ]),
    ]), $history);

    $driver->fetch();

    $query = graphQuery(GraphHttp::graphRequests($history)[0]['request']);
    expect((int) ($query['$top'] ?? $query['top'] ?? 0))->toBe(7);
});

it('settles each outcome into the configured folder and moves the message there', function (SettlementOutcome $outcome, string $folderName, string $folderId) {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::folderCollection($folderId, $folderName),
        GraphHttp::messageParent('folder-inbox'),
        GraphHttp::inboxFolder(),
        GraphHttp::movedMessage(),
    ]), $history);

    $driver->settle('msg-1', $outcome);

    $graph = GraphHttp::graphRequests($history);
    $methods = array_map(fn (array $entry): string => $entry['request']->getMethod(), $graph);
    $uris = array_map(fn (array $entry): string => (string) $entry['request']->getUri(), $graph);

    expect($uris[0])->toContain('mailFolders')
        ->and(urldecode($uris[0]))->toContain($folderName)
        ->and($methods)->toContain('POST');

    $move = null;
    foreach ($graph as $entry) {
        $uri = (string) $entry['request']->getUri();
        if ($entry['request']->getMethod() === 'POST' && str_contains($uri, '/move')) {
            $move = $entry['request'];
        }
    }

    expect($move)->not->toBeNull()
        ->and(requestBody($move)['destinationId'] ?? null)->toBe($folderId);
})->with([
    'processed' => [SettlementOutcome::Processed, 'DoneBox', 'folder-done'],
    'failed' => [SettlementOutcome::Failed, 'DeadLetter', 'folder-dead'],
    'ignored' => [SettlementOutcome::Ignored, 'SkipBin', 'folder-skip'],
]);

it('creates a missing folder then uses the new id for settle', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::emptyFolderCollection(),
        GraphHttp::createdFolder('folder-done-new', 'DoneBox'),
        GraphHttp::messageParent('folder-inbox'),
        GraphHttp::inboxFolder(),
        GraphHttp::movedMessage(),
    ]), $history);

    $driver->settle('msg-1', SettlementOutcome::Processed);

    $graph = GraphHttp::graphRequests($history);
    $create = null;
    $move = null;
    foreach ($graph as $entry) {
        $request = $entry['request'];
        $uri = (string) $request->getUri();
        if ($request->getMethod() === 'POST' && str_contains($uri, 'mailFolders') && ! str_contains($uri, '/move')) {
            $create = $request;
        }
        if ($request->getMethod() === 'POST' && str_contains($uri, '/move')) {
            $move = $request;
        }
    }

    expect($create)->not->toBeNull()
        ->and(requestBody($create)['displayName'] ?? null)->toBe('DoneBox')
        ->and($move)->not->toBeNull()
        ->and(requestBody($move)['destinationId'] ?? null)->toBe('folder-done-new');
});

it('does not POST a move when the message is already in the destination folder', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::folderCollection('folder-done', 'DoneBox'),
        GraphHttp::messageParent('folder-done'),
    ]), $history);

    $driver->settle('msg-1', SettlementOutcome::Processed);

    $movePosts = array_filter(
        GraphHttp::graphRequests($history),
        fn (array $entry): bool => $entry['request']->getMethod() === 'POST'
            && str_contains((string) $entry['request']->getUri(), '/move'),
    );

    expect($movePosts)->toBeEmpty();
});

it('does not throw or move when the parent is not Inbox or Processing', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::folderCollection('folder-dead', 'DeadLetter'),
        GraphHttp::messageParent('folder-done'),
        GraphHttp::inboxFolder(),
        GraphHttp::folderCollection('folder-claim', 'ClaimHold'),
        GraphHttp::folderCollection('folder-done', 'DoneBox'),
    ]), $history);

    $driver->settle('msg-1', SettlementOutcome::Failed);

    $movePosts = array_filter(
        GraphHttp::graphRequests($history),
        fn (array $entry): bool => $entry['request']->getMethod() === 'POST'
            && str_contains((string) $entry['request']->getUri(), '/move'),
    );

    expect($movePosts)->toBeEmpty();
});

it('retries a 429 using the Retry-After delay then succeeds', function () {
    $slept = [];
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(429, [
            'error' => [
                'code' => 'TooManyRequests',
                'message' => 'throttled',
            ],
        ], ['Retry-After' => '7']),
        GraphHttp::json(200, [
            '@odata.deltaLink' => 'https://graph.microsoft.com/v1.0/delta-final',
            'value' => [deltaMessage('msg-after-retry')],
        ]),
    ]), $history, sleeper: function (int $seconds) use (&$slept): void {
        $slept[] = $seconds;
    });

    $page = $driver->fetch();

    expect($slept)->toBe([7])
        ->and($page->messages[0]->externalId)->toBe('msg-after-retry')
        ->and(GraphHttp::graphRequests($history))->toHaveCount(2);
});

it('returns attachment bytes identically including non-UTF8 content', function () {
    $bytes = "\x00\x01\x02\xff\xfe\x80binary";
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'id' => 'att-1',
            'name' => 'payload.bin',
            'contentType' => 'application/octet-stream',
            'size' => strlen($bytes),
            'contentBytes' => base64_encode($bytes),
        ]),
    ]), $history);

    $content = $driver->readAttachment('msg-1', 'att-1');

    expect($content)->toBe($bytes);
});

it('claims a message by moving it to the processing folder', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::folderCollection('folder-claim', 'ClaimHold'),
        GraphHttp::messageParent('folder-inbox'),
        GraphHttp::inboxFolder(),
        GraphHttp::movedMessage(),
        GraphHttp::messageParent('folder-claim'),
    ]), $history);

    expect($driver->claim('msg-1'))->toBeTrue()
        ->and($driver->claim('msg-1'))->toBeFalse();

    $movePosts = array_values(array_filter(
        GraphHttp::graphRequests($history),
        fn (array $entry): bool => $entry['request']->getMethod() === 'POST'
            && str_contains((string) $entry['request']->getUri(), '/move'),
    ));

    expect($movePosts)->toHaveCount(1)
        ->and(requestBody($movePosts[0]['request'])['destinationId'] ?? null)->toBe('folder-claim');
});

it('throws when a delta page has neither nextLink nor deltaLink', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            'value' => [deltaMessage('msg-1')],
        ]),
    ]), $history);

    $driver->fetch();
})->throws(GraphException::class);

it('follows nextLinks up to delta_max_pages_per_poll then returns a continuation cursor', function () {
    config()->set('msgraph.mail.delta_max_pages_per_poll', 2);

    $page2 = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$skiptoken=PAGE2';
    $page3 = 'https://graph.microsoft.com/v1.0/users/mailbox@example.com/mailFolders/inbox/messages/delta?$skiptoken=PAGE3';

    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(200, [
            '@odata.nextLink' => $page2,
            'value' => [deltaMessage('msg-1')],
        ]),
        GraphHttp::json(200, [
            '@odata.nextLink' => $page3,
            'value' => [deltaMessage('msg-2')],
        ]),
    ]), $history);

    $page = $driver->fetch();

    expect($page->messages)->toHaveCount(2)
        ->and($page->messages[0]->externalId)->toBe('msg-1')
        ->and($page->messages[1]->externalId)->toBe('msg-2')
        ->and($page->continuationCursor)->toBe($page3)
        ->and($page->resumeCursor)->toBeNull();

    $graph = GraphHttp::graphRequests($history);
    expect($graph)->toHaveCount(2);
    foreach ($graph as $entry) {
        expect($entry['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
    }
});

it('retries a 429 without Retry-After using exponential backoff', function () {
    $slept = [];
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(429, [
            'error' => [
                'code' => 'TooManyRequests',
                'message' => 'throttled',
            ],
        ]),
        GraphHttp::json(200, [
            '@odata.deltaLink' => 'https://graph.microsoft.com/v1.0/delta-final',
            'value' => [deltaMessage('msg-after-backoff')],
        ]),
    ]), $history, sleeper: function (int $seconds) use (&$slept): void {
        $slept[] = $seconds;
    });

    $page = $driver->fetch();

    expect($slept)->toBe([1])
        ->and($page->messages[0]->externalId)->toBe('msg-after-backoff');

    foreach (GraphHttp::graphRequests($history) as $entry) {
        expect($entry['request']->getHeaderLine('Prefer'))->toBe('IdType="ImmutableId"');
    }
});

it('skips claim moves when the processing folder is empty', function () {
    config()->set('msgraph.mail.folders.processing', '');

    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([]), $history);

    expect($driver->claim('msg-1'))->toBeTrue()
        ->and(GraphHttp::graphRequests($history))->toBeEmpty();
});

it('does not propagate folder failures from settle', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([
        GraphHttp::json(404, [
            'error' => [
                'code' => 'ErrorItemNotFound',
                'message' => 'gone',
            ],
        ]),
    ]), $history);

    $driver->settle('msg-1', SettlementOutcome::Processed);

    expect(GraphHttp::graphRequests($history))->not->toBeEmpty();
});

it('rejects a cursor pointing at a non-Graph host before making a request', function () {
    $history = [];
    $driver = GraphHttp::driver(GraphHttp::mock([]), $history);

    expect(fn () => $driver->fetch('https://attacker.example/steal'))
        ->toThrow(GraphException::class)
        ->and(GraphHttp::graphRequests($history))->toBeEmpty();
});
