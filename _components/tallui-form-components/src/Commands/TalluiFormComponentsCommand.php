<?php

namespace Usetall\TalluiFormComponents\Commands;

use Illuminate\Console\Command;

class TalluiFormComponentsCommand extends Command
{
    public $signature = 'tallui-form-components';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
