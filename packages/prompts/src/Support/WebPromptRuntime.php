<?php

namespace Moox\Prompts\Support;

use Closure;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\Progress;

class WebPromptRuntime implements PromptRuntime
{
    protected PromptResponseStore $responseStore;

    public function __construct()
    {
        $this->responseStore = app('moox.prompts.response_store');
    }

    protected function generatePromptId(string $method): string
    {
        // Wenn der aktuelle Step vom FlowRunner gesetzt wurde, verwenden wir ihn
        // als stabile Prompt-ID, damit jeder Step genau einen Prompt hat und
        // Antworten nicht zwischen Steps vermischt werden.
        if (app()->bound('moox.prompts.current_step')) {
            return app('moox.prompts.current_step');
        }

        // Fallback für generische Nutzung (z.B. CLI oder ohne Flow-Kontext)
        return $this->responseStore->getNextPromptId($method);
    }

    protected function checkOrThrow(string $promptId, array $promptData): mixed
    {
        if ($this->responseStore->has($promptId)) {
            $value = $this->responseStore->get($promptId);

            if ($promptData['method'] === 'multiselect') {
                if (! is_array($value)) {
                    if ($value === true) {
                        $options = $promptData['params'][1] ?? [];

                        return array_keys($options);
                    }
                    if ($value !== null && $value !== '') {
                        return [$value];
                    }

                    return [];
                }

                return $value;
            }

            // Für alle anderen Prompts erwarten wir skalare Werte.
            // Falls dennoch ein Array im Store liegt (z.B. durch Formular-State),
            // wandeln wir es in einen String um, um Typfehler zu vermeiden.
            if (is_array($value)) {
                return implode(', ', array_map('strval', $value));
            }

            return $value;
        }

        throw new PendingPromptsException([
            'id' => $promptId,
            'method' => $promptData['method'],
            'params' => $promptData['params'],
        ]);
    }

    public function text(
        string $label,
        string $placeholder = '',
        mixed $default = null,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        $promptId = $this->generatePromptId('text');

        return $this->checkOrThrow($promptId, [
            'method' => 'text',
            'params' => [
                $label,
                $placeholder,
                $default,
                $required,
                $validate,
                $hint,
                $transform,
            ],
        ]);
    }

    public function textarea(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        $promptId = $this->generatePromptId('textarea');

        return $this->checkOrThrow($promptId, [
            'method' => 'textarea',
            'params' => [
                $label,
                $placeholder,
                $required,
                $validate,
                $hint,
                $transform,
            ],
        ]);
    }

    public function password(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        $promptId = $this->generatePromptId('password');

        return $this->checkOrThrow($promptId, [
            'method' => 'password',
            'params' => [
                $label,
                $placeholder,
                $required,
                $validate,
                $hint,
                $transform,
            ],
        ]);
    }

    public function confirm(
        string $label,
        bool $default = false,
        bool|string $required = false,
        string $yes = 'I accept',
        string $no = 'I decline',
        string $hint = '',
    ): bool {
        $promptId = $this->generatePromptId('confirm');

        return $this->checkOrThrow($promptId, [
            'method' => 'confirm',
            'params' => [
                $label,
                $default,
                $required,
                $yes,
                $no,
                $hint,
            ],
        ]);
    }

    public function select(
        string $label,
        array $options,
        mixed $default = null,
        ?string $scroll = null,
        string $hint = '',
        callable|string|array|null $validate = null,
        callable|string|null $transform = null,
    ): mixed {
        $promptId = $this->generatePromptId('select');

        return $this->checkOrThrow($promptId, [
            'method' => 'select',
            'params' => [
                $label,
                $options,
                $default,
                $scroll,
                $hint,
                $validate,
                $transform,
            ],
        ]);
    }

    public function multiselect(
        string $label,
        array $options,
        array $default = [],
        bool|string $required = false,
        ?string $scroll = null,
        string $hint = '',
        callable|string|array|null $validate = null,
        callable|string|null $transform = null,
    ): array {
        $promptId = $this->generatePromptId('multiselect');

        return $this->checkOrThrow($promptId, [
            'method' => 'multiselect',
            'params' => [
                $label,
                $options,
                $default,
                $required,
                $scroll,
                $hint,
                $validate,
                $transform,
            ],
        ]);
    }

    public function suggest(
        string $label,
        array|Closure $options,
        mixed $default = null,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        string $hint = '',
        callable|string|null $transform = null,
    ): mixed {
        $promptId = $this->generatePromptId('suggest');

        return $this->checkOrThrow($promptId, [
            'method' => 'suggest',
            'params' => [
                $label,
                $options,
                $default,
                $required,
                $validate,
                $placeholder,
                $hint,
                $transform,
            ],
        ]);
    }

    public function search(
        string $label,
        Closure $options,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        ?string $scroll = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): mixed {
        $promptId = $this->generatePromptId('search');

        return $this->checkOrThrow($promptId, [
            'method' => 'search',
            'params' => [
                $label,
                $options,
                $required,
                $validate,
                $placeholder,
                $scroll,
                $hint,
                $transform,
            ],
        ]);
    }

    public function multisearch(
        string $label,
        Closure $options,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        ?string $scroll = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): array {
        $promptId = $this->generatePromptId('multisearch');

        return $this->checkOrThrow($promptId, [
            'method' => 'multisearch',
            'params' => [
                $label,
                $options,
                $required,
                $validate,
                $placeholder,
                $scroll,
                $hint,
                $transform,
            ],
        ]);
    }

    public function pause(string $message = 'Press ENTER to continue'): void {}

    public function note(string $message): void {}

    public function info(string $message): void {}

    public function warning(string $message): void {}

    public function error(string $message): void {}

    public function alert(string $message): void {}

    public function intro(string $message): void {}

    public function outro(string $message): void {}

    public function table(array $headers, array $rows): void {}

    public function spin(Closure $callback, string $message = ''): mixed
    {
        return $callback();
    }

    public function progress(
        string $label,
        iterable|int $steps,
        ?Closure $callback = null,
        string $hint = '',
    ): Progress|array {
        if (is_int($steps)) {
            return [];
        }

        $results = [];
        foreach ($steps as $step) {
            if ($callback) {
                $results[] = $callback($step);
            }
        }

        return $results;
    }

    public function clear(): void {}

    public function form(): FormBuilder
    {
        throw new \RuntimeException('Form builder not yet implemented for web context');
    }
}
