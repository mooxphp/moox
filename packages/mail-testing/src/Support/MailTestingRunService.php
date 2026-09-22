<?php

declare(strict_types=1);

namespace Moox\MailTesting\Support;

use Illuminate\Support\Facades\Storage;
use Moox\MailTemplate\Models\MailLayout;
use Moox\MailTemplate\Models\MailTemplate;
use Moox\MailTemplate\Support\MailTemplateRenderer;
use Moox\MailTesting\Enums\Engine;
use Moox\MailTesting\Enums\PersistBackend;
use Moox\MailTesting\Enums\RunStatus;
use Moox\MailTesting\Models\MailTestingMessage;
use Moox\MailTesting\Models\MailTestingRun;
use RuntimeException;
use Throwable;

final class MailTestingRunService
{
    public function __construct(
        private readonly MailTestingConverter $converter,
        private readonly MailTemplateRenderer $renderer,
        private readonly PayloadResolver $payloadResolver,
    ) {}

    public function execute(MailTestingRun $run): MailTestingRun
    {
        $runStart = hrtime(true);
        $run->status = RunStatus::Running;
        $run->started_at = now();
        $run->processed = 0;
        $run->error = null;
        $run->save();

        $previousEngine = config('mjml.use_php_renderer');
        config(['mjml.use_php_renderer' => $run->engine === Engine::Php]);

        try {
            $template = $this->renderer->find((string) config('mail-testing.template_slug', 'test'));

            if (! $template instanceof MailTemplate) {
                throw new RuntimeException('Test mail template is missing. Create it from the Mail Testing page.');
            }

            $this->applyLayout($template, is_array($run->options) ? ($run->options['layout_id'] ?? null) : null);

            $composeTotal = 0;
            $convertTotal = 0;
            $persistTotal = 0;
            $progressEvery = max(1, (int) config('mail-testing.progress_every', 25));

            for ($position = 1; $position <= $run->count; $position++) {
                $payload = $this->payloadResolver->forRun(
                    is_array($run->options) ? $run->options : [],
                    $run->options_fingerprint,
                    $position,
                );

                $composeStart = hrtime(true);
                $mjml = $this->converter->compose($template, $payload);
                $composeMs = $this->elapsedMs($composeStart);

                $convertStart = hrtime(true);
                $html = $this->converter->convert($mjml, $run->options);
                $convertMs = $this->elapsedMs($convertStart);

                $persistStart = hrtime(true);
                $this->persist($run, $position, $html, $composeMs, $convertMs, $persistStart);
                $persistMs = $this->elapsedMs($persistStart);

                $composeTotal += $composeMs;
                $convertTotal += $convertMs;
                $persistTotal += $persistMs;

                if ($position % $progressEvery === 0 || $position === $run->count) {
                    $run->processed = $position;
                    $run->compose_ms = $composeTotal;
                    $run->convert_ms = $convertTotal;
                    $run->persist_ms = $persistTotal;
                    $run->generation_ms = $composeTotal + $convertTotal;
                    $run->save();
                }
            }

            $run->status = RunStatus::Completed;
            $run->processed = $run->count;
            $run->compose_ms = $composeTotal;
            $run->convert_ms = $convertTotal;
            $run->persist_ms = $persistTotal;
            $run->generation_ms = $composeTotal + $convertTotal;
            $run->total_ms = $this->elapsedMs($runStart);
            $run->finished_at = now();
            $run->save();
        } catch (Throwable $exception) {
            $run->status = RunStatus::Failed;
            $run->error = $exception->getMessage();
            $run->total_ms = $this->elapsedMs($runStart);
            $run->finished_at = now();
            $run->save();

            throw $exception;
        } finally {
            config(['mjml.use_php_renderer' => $previousEngine]);
        }

        return $run->fresh() ?? $run;
    }

    private function persist(
        MailTestingRun $run,
        int $position,
        string $html,
        int $composeMs,
        int $convertMs,
        int $persistStart,
    ): void {
        $path = null;
        $storedHtml = null;

        if ($run->persist_backend === PersistBackend::Storage) {
            $path = MailTestingRun::htmlRelativePath($run->getKey(), $position);
            Storage::disk((string) config('mail-testing.disk', 'local'))->put($path, $html);
        } else {
            $storedHtml = $html;
        }

        MailTestingMessage::query()->create([
            'mail_testing_run_id' => $run->getKey(),
            'position' => $position,
            'html_hash' => NormalizedHtml::hash($html),
            'byte_length' => strlen($html),
            'compose_ms' => $composeMs,
            'convert_ms' => $convertMs,
            'persist_ms' => $this->elapsedMs($persistStart),
            'storage_path' => $path,
            'html' => $storedHtml,
        ]);
    }

    private function applyLayout(MailTemplate $template, mixed $layoutId): void
    {
        if (! is_numeric($layoutId) || (int) $layoutId < 1) {
            return;
        }

        $layout = MailLayout::query()->with('translations')->find((int) $layoutId);

        if (! $layout instanceof MailLayout) {
            throw new RuntimeException(__('mail-testing::translations.layout_missing'));
        }

        $template->mail_layout_id = $layout->getKey();
        $template->unsetRelation('mailLayout');
        $template->setRelation('mailLayout', $layout);
    }

    private function elapsedMs(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
