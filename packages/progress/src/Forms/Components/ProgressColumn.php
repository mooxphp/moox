<?php

namespace Moox\Progress\Forms\Components;

use Closure;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Column;

class ProgressColumn extends Column
{
    protected string $view = 'progress::progress';

    protected ?Closure $progress = null;

    protected string|Closure|null $poll = null;

    protected string|int|Closure|null $width = null;

    protected ?Closure $formatStateUsing = null;

    protected string|Closure|null $color = null;

    public function progress(Closure $callback): static
    {
        $this->progress = $callback;

        return $this;
    }

    public function getProgress(): int|float
    {
        if ($this->progress === null) {
            return floor($this->getStateFromRecord());
        }

        return $this->evaluate($this->progress);
    }

    public function poll(string|Closure $duration): static
    {
        $this->poll = $duration;

        return $this;
    }

    public function getPoll(): ?string
    {
        return $this->evaluate($this->poll) ?? '5s'; // Default: 5 seconds
    }

    public function getState(): int
    {
        $state = $this->getStateFromRecord();

        return is_numeric($state) ? (int) $state : 0;
    }

    public function formatStateUsing(Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function getFormattedState(): string
    {
        $state = $this->getState();

        if ($this->formatStateUsing !== null) {
            return $this->evaluate($this->formatStateUsing, ['state' => $state]);
        }

        return "{$state}%";
    }

    public function width(string|int|Closure|null $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getWidth(): ?string
    {
        if ($this->width !== null) {
            $width = $this->evaluate($this->width);

            return is_string($width) ? $width : (string) $width;
        }

        return '200px'; // Default width
    }

    public function color(string|Closure $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        if ($this->color !== null) {
            $color = $this->evaluate($this->color);

            // Map Filament color names to CSS variables
            $colorMap = [
                'primary' => 'var(--primary-500)',
                'success' => 'var(--success-500)',
                'warning' => 'var(--warning-500)',
                'danger' => 'var(--danger-500)',
                'info' => 'var(--info-500)',
            ];

            if (isset($colorMap[$color])) {
                return $colorMap[$color];
            }

            return $color;
        }

        return 'var(--primary-500)'; // Default fallback
    }

    public function getAlignment(): Alignment
    {
        return parent::getAlignment() ?? Alignment::Center;
    }
}
