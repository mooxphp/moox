<?php

namespace Moox\Clipboard\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class CopyableField extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->disabled()
            ->suffix('')
            ->live()
            ->suffixAction(
                Action::make('copy')
                    ->icon('heroicon-s-clipboard')
                    ->action(function ($livewire, $state) {
                        $livewire->dispatch('copy-to-clipboard-'.$this->getName(), text: $state);
                    })
            )
            ->extraAttributes([
                'x-data' => '{
                    copyToClipboard(text) {
                            const textArea = document.createElement("textarea");
                            textArea.value = text;
                            textArea.style.position = "fixed";
                            textArea.style.opacity = "0";
                            document.body.appendChild(textArea);
                            textArea.select();
                            try {
                                document.execCommand("copy");
                                $tooltip("Copied to clipboard", { timeout: 1500 });
                            } catch (err) {
                                $tooltip("Failed to copy", { timeout: 1500 });
                            }
                            document.body.removeChild(textArea);
                    }
                }',
                'x-on:copy-to-clipboard-'.$this->getName().'.window' => 'copyToClipboard($event.detail.text)',
            ]);
    }

    public static function make(?string $name = null): static
    {
        $component = app(static::class, ['name' => $name]);
        $component->configure();

        return $component;
    }

    public function defaultValue(string|Closure $value): static
    {
        return $this->default($value);
    }
}
