<?php

declare(strict_types=1);

namespace Moox\Demo\Demo;

use Illuminate\Console\Command;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\Steps\DemoLocalizationStep;
use Moox\Demo\Demo\Steps\DemoMediaStep;
use Moox\Demo\Demo\Steps\DemoUserStep;
use Moox\Demo\Demo\Steps\FactoryEntitiesStep;
use Moox\Demo\Demo\Steps\FreshDatabaseStep;
use Moox\Demo\Demo\Steps\PackageSeedersStep;
use Moox\Demo\Seeding\SeedOutput;
use Moox\Demo\Support\MooxPackageDiscovery;

final class DemoRunner
{
    private readonly SeederOrderResolver $resolver;

    private readonly DemoConsole $console;

    public function __construct(
        private readonly Command $command,
    ) {
        $this->resolver = new SeederOrderResolver;
        $this->console = new DemoConsole($this->command);
    }

    public function run(DemoContext $context): int
    {
        $this->applyDemoContext($context);
        SeedOutput::bind($this->console);

        $this->command->info('Moox Demo — seeding installed Moox packages...');
        $this->console->detail("Dataset: {$context->dataset} ({$context->datasetCount} records per factory entity)");

        $this->console->phase('Database');
        (new FreshDatabaseStep($this->command, $this->console))->run($context);

        $seeders = new PackageSeedersStep($this->command, $this->resolver, $this->console);

        $this->console->phase('Static data');
        $seeders->run($context, onlySlugs: ['data']);

        $this->console->phase('Localizations');
        (new DemoLocalizationStep($this->command, $this->console))->run($context);

        $this->console->phase('Media');
        (new DemoMediaStep($this->command, $this->console))->run($context);

        $this->console->phase('Users');
        $seeders->run($context, onlySlugs: ['user']);
        (new DemoUserStep($this->command, $this->console))->run($context);

        $this->console->phase('Package seeders');
        $seeders->run($context, exceptSlugs: ['data', 'demo', 'user']);

        $this->console->phase('Factory entities');
        (new FactoryEntitiesStep($this->command, $this->console))->run($context);

        SeedOutput::bind(null);

        $this->command->newLine();
        $this->command->info('Moox Demo finished.');

        return Command::SUCCESS;
    }

    private function applyDemoContext(DemoContext $context): void
    {
        config([
            'demo.runtime.seeding' => true,
            'demo.runtime.skip_media' => $context->skipMedia,
            'demo.dataset' => $context->dataset,
            'demo.dataset_count' => $context->datasetCount,
            'demo.media.users_path' => $this->resolveDemoUsersMediaPath(),
        ]);
    }

    private function resolveDemoUsersMediaPath(): ?string
    {
        $configured = config('demo.media.users_path');

        if (is_string($configured) && $configured !== '' && is_dir($configured)) {
            return $configured;
        }

        $composerPath = MooxPackageDiscovery::composerPathForPackage('moox/demo');

        if ($composerPath === null) {
            return null;
        }

        $path = dirname($composerPath).'/resources/demo/assets/images/users';

        return is_dir($path) ? $path : null;
    }
}
