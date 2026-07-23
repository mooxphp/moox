<?php

declare(strict_types=1);

namespace Moox\Demo\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;

final class DemoConsole
{
    private ?ProgressIndicator $indicator = null;

    private ?string $indicatorLabel = null;

    public function __construct(
        private readonly Command $command,
    ) {
    }

    public function phase(string $title): void
    {
        $this->stopIndicator();

        $this->command->newLine();
        $this->command->line("<fg=cyan;options=bold>▶ {$title}</>");
    }

    public function startTask(string $label): void
    {
        $this->stopIndicator();

        $this->indicatorLabel = $label;
        $this->indicator = new ProgressIndicator(
            $this->command->getOutput(),
            '🌱 %message%'
        );
        $this->indicator->start($label);
    }

    /**
     * Stop any spinner and print a running label — use before nested seeder output.
     */
    public function beginNestedOutput(string $label): void
    {
        $this->stopIndicator();
        $this->command->line("  <fg=cyan>…</> {$label}");
    }

    public function updateTask(string $label): void
    {
        $this->indicator?->setMessage($label);
    }

    public function finishTask(string $label, ?string $detail = null): void
    {
        if ($this->indicator === null) {
            $this->command->line("  <fg=green>✓</> {$label}".($detail !== null ? " <fg=gray>({$detail})</>" : ''));

            return;
        }

        $this->indicator->finish($detail ?? $label, '✓');
        $this->indicator = null;
        $this->indicatorLabel = null;
    }

    public function failTask(string $label, string $error): void
    {
        if ($this->indicator !== null) {
            $this->indicator->finish($label, '✗');
            $this->indicator = null;
            $this->indicatorLabel = null;
        }

        $this->command->error("  ✗ {$label}: {$error}");
    }

    public function skip(string $label, ?string $reason = null): void
    {
        $this->stopIndicator();

        $suffix = $reason !== null ? " <fg=gray>— {$reason}</>" : '';

        $this->command->line("  <fg=yellow>○</> {$label}{$suffix}");
    }

    public function detail(string $line): void
    {
        $this->command->line("  <fg=gray>│</> {$line}");
    }

    public function created(string $label): void
    {
        $this->command->line("  <fg=gray>│</> <fg=green>+</> {$label}");
    }

    public function progressBar(int $max, string $message): DemoProgressBar
    {
        $this->stopIndicator();

        $bar = new ProgressBar($this->command->getOutput(), max(1, $max));
        $bar->setFormat('  <fg=gray>│</> %current%/%max% [%bar%] %message%');
        $bar->setMessage($message);
        $bar->start();

        return new DemoProgressBar($bar, $this->command, $message);
    }

    private function stopIndicator(): void
    {
        if ($this->indicator === null) {
            return;
        }

        $this->indicator->finish($this->indicatorLabel ?? '');
        $this->indicator = null;
        $this->indicatorLabel = null;
    }
}
