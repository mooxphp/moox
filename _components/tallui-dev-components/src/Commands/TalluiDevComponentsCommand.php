<?php

namespace Usetall\TalluiDevComponents\Commands;

use Illuminate\Console\Command;

class TalluiDevComponentsCommand extends Command
{
    public $signature = 'tallui-dev-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
