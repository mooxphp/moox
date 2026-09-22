<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Facades\Cache;
use Moox\MailTemplate\Models\MailLayout;

final class MailTestingWorkerStatus
{
    public static function queueName(): string
    {
        return (string) config('mail-testing.queues.name', 'mail-testing');
    }

    public static function usesQueue(): bool
    {
        return config('queue.default') !== 'sync';
    }

    public static function isActive(): bool
    {
        if (! self::usesQueue()) {
            return true;
        }

        if (Cache::has(self::heartbeatKey())) {
            return true;
        }

        if (app()->runningUnitTests()) {
            return false;
        }

        return self::processIsListening();
    }

    public static function shouldQueue(): bool
    {
        return self::usesQueue() && self::isActive();
    }

    public static function workerCommand(): string
    {
        return 'php artisan queue:work --queue='.self::queueName().' --tries=1 --timeout=0';
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
            ...self::layoutFlag($state),
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

    /**
     * @param  array<string, mixed>  $state
     * @return list<string>
     */
    private static function layoutFlag(array $state): array
    {
        if (! filled($state['source_layout_id'] ?? null)) {
            return [];
        }

        $slug = MailLayout::query()->whereKey((int) $state['source_layout_id'])->value('slug');

        if (! is_string($slug) || $slug === '') {
            return [];
        }

        return ['--layout='.$slug];
    }

    public static function rememberIfListening(string $queues): void
    {
        if (! self::listensToQueue($queues, self::queueName())) {
            return;
        }

        Cache::put(self::heartbeatKey(), true, 15);
    }

    public static function listensToQueue(string $queues, string $queue): bool
    {
        foreach (explode(',', $queues) as $name) {
            if (trim($name) === $queue) {
                return true;
            }
        }

        return false;
    }

    public static function commandListensToQueue(string $commandLine, string $queue): bool
    {
        if (preg_match('/\bqueue:(?:work|listen)\b/', $commandLine) !== 1) {
            return false;
        }

        if (preg_match('/--queue(?:=|\s+)["\']?([^\s"\']+)/', $commandLine, $matches) === 1) {
            return self::listensToQueue($matches[1], $queue);
        }

        return false;
    }

    public static function heartbeatKey(): string
    {
        return 'mail-testing.worker-heartbeat.'.self::queueName();
    }

    private static function processIsListening(): bool
    {
        $queue = self::queueName();

        foreach (self::phpCommandLines() as $line) {
            if (self::commandListensToQueue($line, $queue)) {
                return true;
            }
        }

        return false;
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
