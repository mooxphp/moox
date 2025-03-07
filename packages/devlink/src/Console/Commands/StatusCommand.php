<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Core\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Show;

class StatusCommand extends Command
{
    use Art, Check, Show;

    protected $signature = 'devlink:status';

    protected $description = 'Show the status of your devlinked packages';

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
        $this->check();
        $this->show();
    }
}
