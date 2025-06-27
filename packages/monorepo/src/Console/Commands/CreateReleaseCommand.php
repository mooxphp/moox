<?php

namespace Moox\Monorepo\Console\Commands;

use Illuminate\Console\Command;
use Moox\Monorepo\Console\Commands\Concerns\HasPackageVersions;

class CreateReleaseCommand extends Command
{
    use HasPackageVersions;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moox:release {package?} {version?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will create a release for a package';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mooxPackages = $this->getMooxPackages();
        $this->displayPackageVersions($mooxPackages);
    }
}
