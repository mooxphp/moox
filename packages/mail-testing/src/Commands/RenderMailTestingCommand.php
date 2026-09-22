<?php

declare(strict_types=1);

namespace Moox\MailTesting\Commands;

use Illuminate\Console\Command;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Models\MailTestingRun;
use Moox\MailTesting\Support\MailTestingRunService;
use Moox\Mjml\Enums\ValidationLevel;

class RenderMailTestingCommand extends Command
{
    protected $signature = 'mail-testing:render
        {--count= : Number of mails to generate}
        {--engine=php : php or node}
        {--persist=storage : storage or database}
        {--layout= : Layout slug. Empty uses the layout on the test template}
        {--validation=soft : skip, soft or strict}
        {--minify : Minify HTML}
        {--beautify : Beautify HTML}
        {--keep-comments : Keep HTML comments}
        {--ignore-includes : Ignore MJML includes}';

    protected $description = 'Generate personalized test mails and time MJML to HTML (no send)';

    public function handle(MailTestingRunService $service): int
    {
        $count = (int) ($this->option('count') ?: config('mail-testing.default_count', 500));
        $min = (int) config('mail-testing.min_count', 1);

        if ($count < $min) {
            $this->error("Count must be at least {$min}.");

            return self::FAILURE;
        }

        $engine = Engine::tryFrom((string) $this->option('engine'));
        $persist = PersistBackend::tryFrom((string) $this->option('persist'));
        $validation = ValidationLevel::tryFrom((string) $this->option('validation'));

        if (! $engine instanceof Engine) {
            $this->error('Engine must be php or node.');

            return self::FAILURE;
        }

        if (! $persist instanceof PersistBackend) {
            $this->error('Persist must be storage or database.');

            return self::FAILURE;
        }

        if (! $validation instanceof ValidationLevel) {
            $this->error('Validation must be skip, soft or strict.');

            return self::FAILURE;
        }

        $layoutId = $this->layoutId();

        if ($layoutId === false) {
            return self::FAILURE;
        }

        $options = [
            'validation_level' => $validation->value,
            'minify' => (bool) $this->option('minify'),
            'beautify' => (bool) $this->option('beautify'),
            'keep_comments' => (bool) $this->option('keep-comments'),
            'ignore_includes' => (bool) $this->option('ignore-includes'),
            'layout_id' => $layoutId,
        ];

        $run = MailTestingRun::query()->create([
            'status' => RunStatus::Pending,
            'engine' => $engine,
            'persist_backend' => $persist,
            'count' => $count,
            'processed' => 0,
            'options' => $options,
            'options_fingerprint' => MailTestingRun::fingerprint($count, $persist, $options),
        ]);

        $this->info("Run {$run->getKey()}: {$count} mails, engine={$engine->value}, persist={$persist->value}");

        $service->execute($run);

        $run->refresh();

        $this->table(
            ['metric', 'ms'],
            [
                ['compose', (string) $run->compose_ms],
                ['convert', (string) $run->convert_ms],
                ['persist', (string) $run->persist_ms],
                ['generation', (string) $run->generation_ms],
                ['total', (string) $run->total_ms],
            ],
        );

        return $run->status === RunStatus::Completed ? self::SUCCESS : self::FAILURE;
    }

    private function layoutId(): int|false|null
    {
        $slug = $this->option('layout');

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        $layout = MailLayout::query()->where('slug', $slug)->first();

        if (! $layout instanceof MailLayout) {
            $this->error("Layout {$slug} was not found.");

            return false;
        }

        return (int) $layout->getKey();
    }
}
