<?php

declare(strict_types=1);

namespace Moox\Demo\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

final class DemoProgressBar
{
    public function __construct(
        private readonly ProgressBar $bar,
        private readonly Command $command,
        private readonly string $defaultMessage,
    ) {}

    public function advance(int $step = 1): void
    {
        $this->bar->advance($step);
    }

    public function setMessage(string $message): void
    {
        $this->bar->setMessage($message);
    }

    public function finish(?string $summary = null): void
    {
        $this->bar->setMessage($summary ?? $this->defaultMessage);

        $this->bar->finish();
        $this->command->newLine();
    }
}
