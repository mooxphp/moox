<?php

declare(strict_types=1);

namespace Moox\Builder\Commands;

use Illuminate\Console\Command;
use Moox\Builder\Builder\Actions\GenerateEntity;
use Moox\Builder\Builder\Actions\PreviewEntity;
use Moox\Builder\Builder\Blocks\MarkdownEditor;
use Moox\Builder\Builder\Blocks\TitleWithSlug;
use Moox\Builder\Builder\Features\SoftDelete;

class BuildTestEntityCommand extends Command
{
    protected $signature = 'mooxbuilder:testrun';

    protected $description = 'Build a test entity with specified blocks and features';

    public function handle(): void
    {
        $entityName = 'Blub';
        $entityNamespace = 'App'; // or 'Package' if you want to test package generation
        $entityPath = app_path(); // or base_path('packages') for package

        $blocks = $this->getBlocks();
        $features = $this->getFeatures();

        $generateEntity = new GenerateEntity($entityName, $entityNamespace, $entityPath, $blocks, $features);
        $generateEntity->execute();

        $previewEntity = new PreviewEntity($entityName, $entityNamespace, $entityPath);
        $previewEntity->execute();

        $this->info('Entity generated and previewed successfully.');
    }

    protected function getBlocks(): array
    {
        return [
            new TitleWithSlug('title', 'slug', 'Title', 'The title'),
            new MarkdownEditor('content', 'Content', 'The content'),
        ];
    }

    protected function getFeatures(): array
    {
        return [
            new SoftDelete,
        ];
    }
}
