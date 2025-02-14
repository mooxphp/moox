<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Cleanup;
use Moox\Devlink\Console\Traits\Finalize;
use Moox\Devlink\Console\Traits\Restore;
use Moox\Devlink\Console\Traits\Status;
use Moox\Devlink\Console\Traits\Unlink;

class UnlinkPackages extends Command
{
    use Art, Check, Cleanup, Finalize, Restore, Status, Unlink;

    protected $signature = 'devlink:unlink';

    protected $description = 'Unlink Moox packages from the project and restore original composer.json';

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
        $this->info('Hello, I will unlink Moox packages from the project and restore original composer.json.');
        $this->check();
        $this->unlink();
        $this->restore();
        $this->cleanup();
        $this->status();
        $this->finalize();
        $this->info('Have a nice dev!');
    }
}
