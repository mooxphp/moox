<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Backup;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Cleanup;
use Moox\Devlink\Console\Traits\Deploy;
use Moox\Devlink\Console\Traits\Finalize;
use Moox\Devlink\Console\Traits\Restore;
use Moox\Devlink\Console\Traits\Unlink;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class DeployCommand extends Command
{
    use Art, Backup, Check, Cleanup, Deploy, Finalize, Restore, Unlink;

    protected $signature = 'devlink:deploy';

    protected $description = 'Prepare a devlinked project for deployment';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

    protected string $errorMessage;

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
        info('Hello, I will prepare your project and composer.json for deployment.');
        $this->check();
        $this->unlink();
        $this->deploy();
        $this->cleanup();
        $this->finalize();

        if ($this->errorMessage) {
            error('Not ready to deploy! Take a break!');
        } else {
            info('Ready to deploy! Have a nice dev!');
            info(' ');
        }
    }
}
