<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Moox\MailInbox\Enums\SettlementOutcome;

/**
 * Inbox-driver mail settings sourced from this package's configuration.
 */
final readonly class MailSettings
{
    /**
     * @param  list<string>  $allowedDeltaHosts
     */
    public function __construct(
        public ?string $processingFolder,
        public string $processedFolder,
        public string $failedFolder,
        public string $ignoredFolder,
        public int $pageSize,
        public array $allowedDeltaHosts,
    ) {}

    /**
     * @param  array{folders?: array{processing?: string|null, processed?: string, failed?: string, ignored?: string}, page_size?: int, allowed_delta_hosts?: list<string>}  $mail
     */
    public static function fromArray(array $mail): self
    {
        $folders = $mail['folders'] ?? [];
        $processing = $folders['processing'] ?? 'Processing';

        return new self(
            processingFolder: is_string($processing) && $processing !== '' ? $processing : null,
            processedFolder: (string) ($folders['processed'] ?? 'Processed'),
            failedFolder: (string) ($folders['failed'] ?? 'Failed'),
            ignoredFolder: (string) ($folders['ignored'] ?? 'Ignored'),
            pageSize: max(1, (int) ($mail['page_size'] ?? 50)),
            allowedDeltaHosts: self::hostsFrom($mail['allowed_delta_hosts'] ?? null),
        );
    }

    public static function fromConfig(): self
    {
        /** @var array<string, mixed> $mail */
        $mail = config('msgraph.mail', []);

        return self::fromArray(is_array($mail) ? $mail : []);
    }

    public function folderFor(SettlementOutcome $outcome): string
    {
        return match ($outcome) {
            SettlementOutcome::Processed => $this->processedFolder,
            SettlementOutcome::Failed => $this->failedFolder,
            SettlementOutcome::Ignored => $this->ignoredFolder,
        };
    }

    /**
     * @param  list<string>|null  $hosts
     * @return list<string>
     */
    private static function hostsFrom(?array $hosts): array
    {
        if ($hosts === null || $hosts === []) {
            return CursorHostGuard::defaultHosts();
        }

        return array_values(array_filter($hosts, fn (mixed $host): bool => is_string($host) && $host !== ''));
    }
}
