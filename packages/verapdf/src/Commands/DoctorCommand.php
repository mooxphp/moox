<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Commands;

use Illuminate\Console\Command;
use Moox\VeraPdf\Commands\Concerns\InteractsWithVeraPdfEnvironment;
use Moox\VeraPdf\Services\VeraPdfService;

class DoctorCommand extends Command
{
    use InteractsWithVeraPdfEnvironment;

    protected $signature = 'verapdf:doctor';

    protected $description = 'Check veraPDF installation health';

    public function handle(VeraPdfService $veraPdf): int
    {
        return $this->renderVeraPdfHealth($veraPdf->inspectHealth());
    }
}
