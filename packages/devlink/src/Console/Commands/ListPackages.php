<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Show;

class ListPackages extends Command
{
    use Art, Check, Show;

    protected $signature = 'devlink:list';

    protected $description = 'List all devlinked packages in the project';

    protected array $basePaths;

    protected array $packages;

    protected string $composerJsonPath;

    protected string $packagesPath;

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
