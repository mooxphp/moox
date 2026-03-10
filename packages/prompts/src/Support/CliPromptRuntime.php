<?php

namespace Moox\Prompts\Support;

use Closure;
use Laravel\Prompts;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\Progress;

class CliPromptRuntime implements PromptRuntime
{
    public function text(
        string $label,
        string $placeholder = '',
        mixed $default = null,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        return Prompts\text(
            label: $label,
            placeholder: $placeholder,
            default: $default ?? '',
            required: $required,
            validate: $validate,
            hint: $hint,
            transform: $transform,
        );
    }

    public function textarea(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        return Prompts\textarea(
            label: $label,
            placeholder: $placeholder,
            required: $required,
            validate: $validate,
            hint: $hint,
            transform: $transform,
        );
    }

    public function password(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string {
        return Prompts\password(
            label: $label,
            placeholder: $placeholder,
            required: $required,
            validate: $validate,
            hint: $hint,
            transform: $transform,
        );
    }

    public function confirm(
        string $label,
        bool $default = false,
        bool|string $required = false,
        string $yes = 'I accept',
        string $no = 'I decline',
        string $hint = '',
    ): bool {
        return Prompts\confirm(
            label: $label,
            default: $default,
            required: $required,
            yes: $yes,
            no: $no,
            hint: $hint,
        );
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
        return Prompts\select(
            label: $label,
            options: $options,
            default: $default,
            scroll: $scroll !== null ? (int) $scroll : 5,
            hint: $hint,
            validate: $validate,
            transform: $transform,
        );
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
        return Prompts\multiselect(
            label: $label,
            options: $options,
            default: $default,
            scroll: $scroll !== null ? (int) $scroll : 5,
            required: $required,
            hint: $hint,
            validate: $validate,
            transform: $transform,
        );
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
        return Prompts\suggest(
            label: $label,
            options: $options,
            placeholder: $placeholder,
            default: $default ?? '',
            required: $required,
            validate: $validate,
            hint: $hint,
            transform: $transform,
        );
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
        $params = [
            'label' => $label,
            'options' => $options,
            'placeholder' => $placeholder,
            'scroll' => $scroll !== null ? (int) $scroll : 5,
            'validate' => $validate,
            'hint' => $hint,
            'transform' => $transform,
        ];

        if ($required !== false) {
            $params['required'] = $required;
        }

        return Prompts\search(...$params);
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
        return Prompts\multisearch(
            label: $label,
            options: $options,
            placeholder: $placeholder,
            scroll: $scroll !== null ? (int) $scroll : 5,
            required: $required,
            validate: $validate,
            hint: $hint,
            transform: $transform,
        );
    }

    public function pause(string $message = 'Press ENTER to continue'): void
    {
        Prompts\pause($message);
    }

    public function note(string $message): void
    {
        Prompts\note($message);
    }

    public function info(string $message): void
    {
        Prompts\info($message);
    }

    public function warning(string $message): void
    {
        Prompts\warning($message);
    }

    public function error(string $message): void
    {
        Prompts\error($message);
    }

    public function alert(string $message): void
    {
        Prompts\alert($message);
    }

    public function intro(string $message): void
    {
        Prompts\intro($message);
    }

    public function outro(string $message): void
    {
        Prompts\outro($message);
    }

    public function table(array $headers, array $rows): void
    {
        Prompts\table($headers, $rows);
    }

    public function spin(
        Closure $callback,
        string $message = '',
    ): mixed {
        return Prompts\spin($callback, $message);
    }

    public function progress(
        string $label,
        iterable|int $steps,
        ?Closure $callback = null,
        string $hint = '',
    ): Progress|array {
        return Prompts\progress(
            label: $label,
            steps: $steps,
            callback: $callback,
            hint: $hint,
        );
    }

    public function clear(): void
    {
        Prompts\clear();
    }

    public function form(): FormBuilder
    {
        return Prompts\form();
    }
}
