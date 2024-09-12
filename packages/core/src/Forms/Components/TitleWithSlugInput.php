<?php

namespace Moox\Core\Forms\Components;

use Closure;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class TitleWithSlugInput
{
    protected string $name;

    protected ?string $titleLabel = null;

    protected ?string $slugLabel = null;

    protected $showSlugInput = true;

    protected ?string $slugPrefix = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function titleLabel(?string $label): self
    {
        $this->titleLabel = $label;

        return $this;
    }

    public function slugLabel(?string $label): self
    {
        $this->slugLabel = $label;

        return $this;
    }

    public function showSlugInput($condition): self
    {
        $this->showSlugInput = $condition;

        return $this;
    }

    public function slugPrefix(?string $prefix): self
    {
        $this->slugPrefix = $prefix;

        return $this;
    }

    public function components(): array
    {
        return [
            TextInput::make($this->name)
                ->label($this->titleLabel ?? __('core::core.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, $set) {
                    if (! $state || $this->evaluateShowSlugInput()) {
                        $set('slug', Str::slug($state));
                    }
                }),

            TextInput::make('slug')
                ->label($this->slugLabel ?? __('core::core.slug'))
                ->required()
                ->unique(ignoreRecord: true)
                ->rules(['max:255'])
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))
                ->prefix($this->slugPrefix)
                ->hidden(fn () => ! $this->evaluateShowSlugInput()),
        ];
    }

    protected function evaluateShowSlugInput(): bool
    {
        if ($this->showSlugInput instanceof Closure) {
            return call_user_func($this->showSlugInput, null);
        }

        return $this->showSlugInput;
    }
}
