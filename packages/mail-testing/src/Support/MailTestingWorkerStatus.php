<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Facades\Cache;

final class MailTestingWorkerStatus
{
    public const DEFAULT_QUEUE = 'default';

    public static function queueName(): string
    {
        return (string) config('mail-testing.queues.name', 'mail-testing');
    }

    /**
     * @return list<string>
     */
    public static function acceptedQueues(): array
    {
        $configured = self::queueName();
        $queues = [$configured];

        if ($configured !== self::DEFAULT_QUEUE) {
            $queues[] = self::DEFAULT_QUEUE;
        }

        return $queues;
    }

    public static function usesQueue(): bool
    {
        return config('queue.default') !== 'sync';
    }

    public static function isActive(): bool
    {
        foreach (self::acceptedQueues() as $queue) {
            if (self::isListeningTo($queue)) {
                return true;
            }
        }

        return false;
    }

    public static function shouldQueue(): bool
    {
        return self::usesQueue() && self::isActive();
    }

    public static function targetQueue(): string
    {
        $preferred = self::queueName();

        if (self::isListeningTo($preferred)) {
            return $preferred;
        }

        if ($preferred !== self::DEFAULT_QUEUE && self::isListeningTo(self::DEFAULT_QUEUE)) {
            return self::DEFAULT_QUEUE;
        }

        return $preferred;
    }

    public static function workerCommand(): string
    {
        return 'php artisan queue:work --queue='.implode(',', self::acceptedQueues()).' --tries=1 --timeout=0';
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function renderCommand(array $state): string
    {
        $parts = [
            'php artisan mail-testing:render',
            '--count='.(int) ($state['count'] ?? 1),
            '--engine='.(string) ($state['engine'] ?? 'php'),
            '--persist='.(string) ($state['persist_backend'] ?? 'storage'),
            ...self::templateFlag($state),
            ...self::localeFlag($state),
            '--validation='.(string) ($state['validation_level'] ?? 'soft'),
        ];

        foreach ([
            'minify' => '--minify',
            'beautify' => '--beautify',
            'keep_comments' => '--keep-comments',
            'ignore_includes' => '--ignore-includes',
        ] as $key => $flag) {
            if (! empty($state[$key])) {
                $parts[] = $flag;
            }
        }

        return implode(' ', $parts);
    }

    public static function rememberIfListening(string $queues): void
    {
        foreach (self::acceptedQueues() as $queue) {
            if (! self::listensToQueue($queues, $queue)) {
                continue;
            }

            Cache::put(self::heartbeatKey($queue), true, 15);
        }
    }

    public static function commandListensToQueue(string $commandLine, string $queue): bool
    {
        if (preg_match('/\bqueue:(?:work|listen)\b/', $commandLine) !== 1) {
            return false;
        }

        if (preg_match('/--queue(?:=|\s+)["\']?([^\s"\']+)/', $commandLine, $matches) === 1) {
            return self::listensToQueue($matches[1], $queue);
        }

        return $queue === self::DEFAULT_QUEUE;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    private static function templateFlag(array $state): array
    {
        $slug = $state['source_template_slug'] ?? null;

        if (! is_string($slug) || $slug === '') {
            return [];
        }

        return ['--template='.$slug];
    }

    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    private static function localeFlag(array $state): array
    {
        $locale = $state['source_template_locale'] ?? null;

        if (! is_string($locale) || $locale === '') {
            return [];
        }

        return ['--locale='.$locale];
    }

    private static function isListeningTo(string $queue): bool
    {
        if (Cache::has(self::heartbeatKey($queue))) {
            return true;
        }

        if (app()->runningUnitTests()) {
            return false;
        }

        foreach (self::phpCommandLines() as $line) {
            if (self::commandListensToQueue($line, $queue)) {
                return true;
            }
        }

        return false;
    }

    private static function listensToQueue(string $queues, string $queue): bool
    {
        foreach (explode(',', $queues) as $name) {
            if (trim($name) === $queue) {
                return true;
            }
        }

        return false;
    }

    private static function heartbeatKey(string $queue): string
    {
        return 'mail-testing.worker-heartbeat.'.$queue;
    }

    /**
     * @return list<string>
     */
    private static function phpCommandLines(): array
    {
        $raw = PHP_OS_FAMILY === 'Windows'
            ? shell_exec('powershell -NoProfile -NonInteractive -Command "Get-CimInstance Win32_Process | Where-Object { $_.Name -like \'php*\' } | ForEach-Object { $_.CommandLine }"')
            : shell_exec('ps -ww -eo args=');

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\n/', $raw);

        return is_array($lines) ? array_values(array_filter($lines)) : [];
    }
}
