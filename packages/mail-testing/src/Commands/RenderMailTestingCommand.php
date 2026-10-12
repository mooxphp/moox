<?php

declare(strict_types=1);

namespace Moox\MailTesting\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Moox\MailTemplate\Models\MailTemplate;
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
        {--template= : Mail template slug}
        {--locale= : Translation locale on the template}
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

        $templateSlug = $this->templateSlug();

        if ($templateSlug === false) {
            return self::FAILURE;
        }

        $locale = $this->locale($templateSlug);

        if ($locale === false) {
            return self::FAILURE;
        }

        $options = [
            'validation_level' => $validation->value,
            'minify' => (bool) $this->option('minify'),
            'beautify' => (bool) $this->option('beautify'),
            'keep_comments' => (bool) $this->option('keep-comments'),
            'ignore_includes' => (bool) $this->option('ignore-includes'),
            'template_slug' => $templateSlug,
            'locale' => $locale,
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

        $this->info("Run {$run->getKey()}: {$count} mails, template={$templateSlug}".($locale !== null ? ", locale={$locale}" : '').", engine={$engine->value}, persist={$persist->value}");

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

    private function templateSlug(): string|false
    {
        $slug = $this->option('template');

        if (! is_string($slug) || $slug === '') {
            $this->error('A template slug is required.');

            return false;
        }

        $template = MailTemplate::query()->where('slug', $slug)->first();

        if (! $template instanceof MailTemplate) {
            $this->error("Template {$slug} was not found.");

            return false;
        }

        return $slug;
    }

    private function locale(string $templateSlug): string|false|null
    {
        $locale = $this->option('locale');

        if (! is_string($locale) || $locale === '') {
            return null;
        }

        $exists = MailTemplate::query()
            ->where('slug', $templateSlug)
            ->whereHas('translations', function (Builder $query) use ($locale): void {
                $query->where('locale', $locale);
            })
            ->exists();

        if (! $exists) {
            $this->error("Locale {$locale} is not on template {$templateSlug}.");

            return false;
        }

        return $locale;
    }
}
