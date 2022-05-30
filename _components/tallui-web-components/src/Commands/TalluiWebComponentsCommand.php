<?php

namespace Usetall\TalluiWebComponents\Commands;

use Illuminate\Console\Command;

class TalluiWebComponentsCommand extends Command
{
    public $signature = 'tallui-web-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
