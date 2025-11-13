<?php

namespace Moox\Devlink\Console\Traits;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

trait Show
{
    private const CHECK_MARK = "\u{2714}"; // ✔

    private const CROSS_MARK = "\u{2718}"; // ✘

    private const STOP = "\u{1F6AB}"; // 🚫

    private const ROCKET = "\u{1F680}"; // 🚀

    private const FIRE = "\u{1F525}"; // 🔥

    private const LINK = "\u{1F517}"; // 🔗

    private const STAR = "\u{2B50}"; // ⭐

    private function show(): void
    {
        $fullStatus = $this->check();
        $isInSync = $this->arePackagesInSync($fullStatus['packages']);

        $headers = ['Package', 'Type', 'Enabled', 'Valid', 'Active', 'Version', 'Path'];
        $rows = array_map(function ($row) {
            $type = match ($row['type']) {
                'local' => '<fg=yellow>local</>',
                'private' => '<fg=red>private</>',
                'public' => '<fg=green>public</>',
                default => $row['type'],
            };

            $version = $this->getInstalledVersion($row['name'], $row['config']);
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

        $icon = self::STOP;
        $badge = '<fg=gray;bg=black;options=bold> ';
        $updateBadge = '<fg=gray;bg=black;options=bold> ';

        if ($fullStatus['status'] === 'error') {
            $icon = self::STOP;
            $badge = '<fg=red;bg=black;options=bold> ';
        }

        if ($fullStatus['status'] === 'deploy') {
            $icon = self::ROCKET;
            $badge = '<fg=blue;bg=black;options=bold> ';
        }

        if ($fullStatus['status'] === 'linked') {
            $icon = self::LINK;
            $badge = '<fg=green;bg=black;options=bold> ';
        }

        if ($isInSync) {
            $updateIcon = self::STAR;
            $updateBadge = '<fg=green;bg=black;options=bold> ';
        } else {
            $updateIcon = self::FIRE;
            $updateBadge = '<fg=red;bg=black;options=bold> ';
        }

        info('  '.$icon.$badge.strtoupper($fullStatus['status']).' </> '.$fullStatus['message']);
        info('  '.$updateIcon.$updateBadge.'UPDATE </> '.($isInSync ? 'All packages are in sync with composer.json' : 'You need to run `php artisan moox:devlink` to update the packages'));

        if (! $isInSync && $this->getOutput()->isVerbose()) {
            info(' ');
            info('Detailed sync status:');

            foreach ($fullStatus['packages'] as $package) {
                $packageName = $this->getPackageName($package['name'], $package['config']);
                if (! $packageName) {
                    continue;
                }

                $expectedPath = $package['config']['path'] ?? '';
                if (empty($expectedPath)) {
                    continue;
                }

                $composerJson = json_decode(file_get_contents($this->composerJsonPath), true);
                $composerRequire = array_merge(
                    $composerJson['require'] ?? [],
                    $composerJson['require-dev'] ?? []
                );

                if (! isset($composerRequire[$packageName])) {
                    info("  <fg=red>✘</> {$packageName}: Not found in composer.json requirements");

                    continue;
                }

                $composerPath = $composerRequire[$packageName];
                if (str_contains($composerPath, 'path:')) {
                    $composerPath = trim(str_replace('path:', '', $composerPath));
                    if ($composerPath !== $expectedPath) {
                        info("  <fg=red>✘</> {$packageName}: Path mismatch");
                        info("     Expected: {$expectedPath}");
                        info("     Found: {$composerPath}");
                    } else {
                        info("  <fg=green>✓</> {$packageName}: Correctly linked");
                    }
                }
            }
        }

        info(' ');
    }
}
