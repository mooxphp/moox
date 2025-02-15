<?php

namespace Moox\Devlink\Console\Commands;

use Illuminate\Console\Command;
use Moox\Devlink\Console\Traits\Art;
use Moox\Devlink\Console\Traits\Check;
use Moox\Devlink\Console\Traits\Cleanup;
use Moox\Devlink\Console\Traits\Finalize;
use Moox\Devlink\Console\Traits\Restore;
use Moox\Devlink\Console\Traits\Show;
use Moox\Devlink\Console\Traits\Unlink;

use function Laravel\Prompts\info;

class UnlinkCommand extends Command
{
    use Art, Check, Cleanup, Finalize, Restore, Show, Unlink;

    protected $signature = 'devlink:unlink';

    protected $description = 'Unlink Moox packages from the project and restore original composer.json';

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
        info('Hello, I will unlink Moox packages from the project and restore original composer.json.');
        $this->check();
        $this->unlink();
        $this->restore();
        $this->cleanup();
        $this->finalize();
        info('Packages unlinked! Have a nice dev!');
    }
}
