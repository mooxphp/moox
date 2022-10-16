<?php

namespace Usetall\TalluiFormComponents\Commands;

use Illuminate\Console\Command;

class TalluiFormComponentsCommand extends Command
{
    public $signature = 'tallui-form-components';

    public $description = 'The form components demo command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
