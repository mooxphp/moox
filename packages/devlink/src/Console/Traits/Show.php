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

        $headers = ['Package', 'Type', 'Active', 'Link', 'Deploy', 'Valid', 'Linked'];
        $rows = array_map(function ($row) {
            return [
                $row['name'],
                $row['type'],
                $row['active'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['link'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['deploy'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['valid'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
                $row['linked'] ? '<fg=green>   '.self::CHECK_MARK.'   </>' : '<fg=red>   '.self::CROSS_MARK.'   </>',
            ];
        }, $fullStatus['packages']);

        table($headers, $rows);

        $badge = '<fg=black;bg=yellow;options=bold> ';

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

        info('  '.$badge.strtoupper($fullStatus['status']).' </> '.$fullStatus['message']);
        info(' ');
    }
}
