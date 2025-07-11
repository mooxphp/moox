<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Finalize;
use Moox\Devlink\Console\Traits\Link;

use function Laravel\Prompts\info;

class LinkCommand extends Command
{
    use Check, Finalize, Link;

    protected $signature = 'moox:devlink';

    protected $description = 'Symlink Moox packages into the project from multiple base paths and ensure composer.json is updated';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

    protected string $errorMessage = '';

    public function __construct()
    {
        parent::__construct();

        $this->packages = config('devlink.packages', []);
        $this->composerJsonPath = base_path('composer.json');
        $this->packagesPath = config('devlink.packages_path', base_path('packages'));
    }

    public function handle(): void
    {
        // $this->art();
        info('Hello, I will link the configured packages for you.');
        $this->check();
        // create a symlink for the DEVLOG.md file

        $this->link();
        $this->finalize();
        info('Packages linked! Have a nice dev!');
    }
}
