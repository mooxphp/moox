<?php

namespace Moox\Clipboard\Forms\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Js;

class CopyableField extends TextInput
{
    protected function setUp(): void
    {
        parent::setUp();

        $btnAttr = 'copy-btn-'.$this->getName();

        $this->disabled()
            ->suffix('')
            ->live()
            ->suffixAction(
                Action::make('copy')
                    ->icon('heroicon-m-clipboard')
                    ->extraAttributes([
                        'data-copy-button' => $btnAttr,
                    ])
                    ->action(function ($livewire, $state) use ($btnAttr) {
                        $livewire->js(sprintf(
                            'const doSwap = () => {
                                const btn = document.querySelector(`[data-copy-button=%s]`);
                                if (!btn) return;
                                const svg = btn.querySelector("svg");
                                if (!svg) return;
                                const original = svg.outerHTML;

                                const checkSvg = `<svg xmlns="http://www.w3.org/2000/svg" 
                                    viewBox="0 0 20 20" fill="currentColor" 
                                    style="width: 24px; height: 24px; color: #10b981">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 0 1.414l-7.5 7.5a1 1 0 0 1-1.414 0l-3-3a1 1 0 1 1 1.414-1.414L8.5 12.086l6.793-6.793a1 1 0 0 1 1.414 0z" clip-rule="evenodd"/>
                                    </svg>`;

                                svg.outerHTML = checkSvg;
                                setTimeout(() => {
                                    const cur = btn.querySelector("svg");
                                    if (cur) cur.outerHTML = original;
                                }, 1200);
                            };

                            const text = %s;
                            const copy = async () => {
                                try {
                                    if (navigator.clipboard && window.isSecureContext) {
                                        await navigator.clipboard.writeText(text);
                                    } else {
                                        const ta = document.createElement("textarea");
                                        ta.value = text;
                                        ta.style.position = "fixed";
                                        ta.style.opacity = "0";
                                        document.body.appendChild(ta);
                                        ta.select();
                                        document.execCommand("copy");
                                        document.body.removeChild(ta);
                                    }
                                    $dispatch("notify", { message: "Copied to clipboard", type: "success" });
                                    doSwap();
                                } catch (e) {
                                    $dispatch("notify", { message: "Failed to copy", type: "error" });
                                }
                            };
                            copy();',
                            Js::from($btnAttr),
                            Js::from($state)
                        ));
                    })
            );
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
