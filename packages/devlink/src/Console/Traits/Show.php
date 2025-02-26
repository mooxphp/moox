<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

trait Show
{
    private const CHECK_MARK = "\u{2714}"; // ✔

    private const CROSS_MARK = "\u{2718}"; // ✘

    private function show(): void
    {
        $fullStatus = $this->check();

        $headers = ['Package', 'Type', 'Active', 'Valid', 'Linked', 'Version', 'Path'];
        $rows = array_map(function ($row) {
            $type = match ($row['type']) {
                'local' => '<fg=yellow>local</>',
                'private' => '<fg=red>private</>',
                'public' => '<fg=green>public</>',
                default => $row['type'],
            };

            $version = $this->getInstalledVersion('moox/'.$row['name']);
            $path = $this->getShortPath($row);

            return [
                $row['name'],
                $type,
                $row['active'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['valid'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['linked'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $version ?: '-',
                $path,
            ];
        }, $fullStatus['packages']);

        table($headers, $rows);

        $badge = '<fg=black;bg=yellow;options=bold> ';
        $updateBadge = '<fg=black;bg=yellow;options=bold> ';

        if ($fullStatus['status'] === 'error') {
            $badge = '<fg=black;bg=red;options=bold> ';
        }

        if ($fullStatus['status'] === 'unlinked') {
            $badge = '<fg=black;bg=gray;options=bold> ';
        }

        if ($fullStatus['status'] === 'linked') {
            $badge = '<fg=black;bg=green;options=bold> ';
        }

        if ($fullStatus['status'] === 'deployed') {
            $badge = '<fg=black;bg=green;options=bold> ';
        }

        if ($fullStatus['updated']) {
            $updateBadge = '<fg=black;bg=green;options=bold> ';
        } else {
            $updateBadge = '<fg=black;bg=red;options=bold> ';
        }

        info('  '.$badge.strtoupper($fullStatus['status']).' </> '.$fullStatus['message']);
        info('  '.$updateBadge.' UPDATE </> '.($fullStatus['updated'] ? 'All packages are in sync with composer.json' : 'You need to run `php artisan devlink:link` to update the packages'));
        info(' ');
    }

    private function getInstalledVersion(string $package): ?string
    {
        $composerLock = base_path('composer.lock');
        if (! file_exists($composerLock)) {
            return null;
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        foreach ([$lockData['packages'] ?? [], $lockData['packages-dev'] ?? []] as $packages) {
            foreach ($packages as $pkg) {
                if ($pkg['name'] === $package) {
                    return $pkg['version'];
                }
            }
        }

        return null;
    }

    private function getShortPath(array $row): string
    {
        if (($row['type'] ?? '') === 'local') {
            return '-';
        }

        $privateBasePath = config('devlink.private_base_path');
        if (($row['type'] ?? '') === 'private' && $privateBasePath === 'disabled') {
            return '- enable private path in config -';
        }

        $path = $this->packages[$row['name']]['path'] ?? '';
        if (empty($path)) {
            return '-';
        }

        if (str_starts_with($path, '../')) {
            return $path;
        }

        $basePath = base_path();
        if (str_starts_with($path, $basePath)) {
            return substr($path, strlen($basePath) + 1);
        }

        return $path;
    }
}
