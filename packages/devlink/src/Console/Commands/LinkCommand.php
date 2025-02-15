<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Backup;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Finalize;
use Moox\Devlink\Console\Traits\Link;
use Moox\Devlink\Console\Traits\Prepare;
use Moox\Devlink\Console\Traits\Restore;

use function Laravel\Prompts\info;

class LinkCommand extends Command
{
    use Art, Backup, Check, Finalize, Link, Prepare, Restore;

    protected $signature = 'devlink:link';

    protected $description = 'Symlink Moox packages into the project from multiple base paths and ensure composer.json is updated';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

    protected string $errorMessage = '';

    public function __construct()
    {
        parent::__construct();

        $this->basePaths = config('devlink.base_paths', []);
        $this->packages = config('devlink.packages', []);
        $this->composerJsonPath = base_path('composer.json');
        $this->packagesPath = config('devlink.packages_path', base_path('packages'));
    }

    public function handle(): void
    {
        $this->art();
        info('Hello, I will link the configured packages for you.');
        $this->check();
        $this->backup();
        $this->prepare();
        $this->link();
        $this->finalize();
        info('Packages linked! Have a nice dev!');
    }
}
