<?php

namespace Usetall\TalluiCore\Commands;

use Illuminate\Console\Command;

class TalluiCoreCommand extends Command
{
    public $signature = 'tallui-core';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
