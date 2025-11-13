<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Deploy;
use Moox\Devlink\Console\Traits\Finalize;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class DeployCommand extends Command
{
    use Check, Deploy, Finalize;

    protected $signature = 'moox:deploy';

    protected $description = 'Prepare a devlinked project for deployment';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

    private string $errorMessage = '';

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
        info('Hello, I will prepare your project and composer.json for deployment.');
        $this->check();
        $this->deploy();
        $this->finalize();

        if ($this->errorMessage) {
            error('Not ready to deploy! Take a break!');
        } else {
            info('Ready to deploy! Have a nice dev!');
            info(' ');
        }
    }
}
