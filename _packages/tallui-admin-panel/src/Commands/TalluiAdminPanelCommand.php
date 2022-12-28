<?php

namespace Usetall\TalluiAdminPanel\Commands;

use Illuminate\Console\Command;

class TalluiAdminPanelCommand extends Command
{
    public $signature = 'tallui-admin-panel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
