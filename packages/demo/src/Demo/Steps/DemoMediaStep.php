<?php

declare(strict_types=1);

namespace Moox\Demo\Demo\Steps;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Moox\Demo\Console\DemoConsole;
use Moox\Demo\Demo\DemoContext;
use Moox\Media\Models\Media;

final class DemoMediaStep
{
    public function __construct(
        private readonly Command $command,
        private readonly DemoConsole $console,
    ) {}

    public function run(DemoContext $context): void
    {
        if ($context->skipMedia) {
            $this->console->skip('Demo media', 'skipped via --skip-media');

            return;
        }

        $sourceDir = dirname(__DIR__, 3).'/resources/demo/media';

        if (! is_dir($sourceDir)) {
            if ($this->command->getOutput()->isVerbose()) {
                $this->console->skip('Demo media', 'no media directory found');
            }

            return;
        }

        $disk = (string) config('demo.media.disk', 'public');
        $directory = (string) config('demo.media.directory', 'demo');

        $files = File::files($sourceDir);

        if ($files === []) {
            $this->console->skip('Demo media', 'no files to copy');

            return;
        }

        $this->console->beginNestedOutput('Demo media');

        foreach ($files as $file) {
            $relative = $directory.'/'.$file->getFilename();
            Storage::disk($disk)->put($relative, File::get($file->getPathname()));
            $this->console->created($file->getFilename());
        }

        $this->console->finishTask('Demo media', count($files).' file(s) copied');

        if (class_exists(Media::class)) {
            $this->console->detail('moox/media is installed; entity seeders attach media via Mediathek.');
        }
    }
}
