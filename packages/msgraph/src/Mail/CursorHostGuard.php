<?php

declare(strict_types=1);

namespace Moox\MsGraph\Mail;

use Microsoft\Graph\Core\NationalCloud;
use Moox\MailInbox\Exceptions\InvalidSyncCursorException;

/**
 * Rejects delta cursors that would send the Graph bearer token to a non-Graph host.
 */
final class CursorHostGuard
{
    /**
     * @param  list<string>  $allowedHosts
     */
    public function __construct(private array $allowedHosts) {}

    /**
     * @return list<string>
     */
    public static function defaultHosts(): array
    {
        $hosts = [];
        foreach ((new \ReflectionClass(NationalCloud::class))->getConstants() as $url) {
            if (! is_string($url)) {
                continue;
            }
            $host = parse_url($url, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                $hosts[] = strtolower($host);
            }
        }

        return array_values(array_unique($hosts));
    }

    public function assertAllowed(?string $cursor): void
    {
        if ($cursor === null || $cursor === '') {
            return;
        }

        $parts = parse_url($cursor);
        $scheme = is_array($parts) ? ($parts['scheme'] ?? null) : null;
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;

        if ($scheme !== 'https' || ! is_string($host) || $host === '') {
            throw new InvalidSyncCursorException(
                'Delta cursor is not an https Graph URL.',
                rejectedHost: is_string($host) && $host !== '' ? $host : '(missing)',
            );
        }

        $allowed = array_map(strtolower(...), $this->allowedHosts);
        if (! in_array(strtolower($host), $allowed, true)) {
            throw new InvalidSyncCursorException(
                "Delta cursor host [{$host}] is not an allowed Graph endpoint.",
                rejectedHost: $host,
            );
        }
    }
}
