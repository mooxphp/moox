<?php

namespace Usetall\TalluiPackageBuilder\Commands;

use Illuminate\Console\Command;

class TalluiPackageBuilderCommand extends Command
{
    public $signature = 'skeleton';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
