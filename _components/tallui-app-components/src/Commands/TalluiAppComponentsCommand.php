<?php

namespace Usetall\TalluiAppComponents\Commands;

use Illuminate\Console\Command;

class TalluiAppComponentsCommand extends Command
{
    public $signature = 'tallui-app-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
