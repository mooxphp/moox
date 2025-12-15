<?php

namespace Moox\Prompts\Support;

use Closure;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\Progress;

interface PromptRuntime
{
    public function text(
        string $label,
        string $placeholder = '',
        mixed $default = null,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string;

    public function textarea(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string;

    public function password(
        string $label,
        string $placeholder = '',
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): string;

    public function confirm(
        string $label,
        bool $default = false,
        bool|string $required = false,
        string $yes = 'I accept',
        string $no = 'I decline',
        string $hint = '',
    ): bool;

    public function select(
        string $label,
        array $options,
        mixed $default = null,
        ?string $scroll = null,
        string $hint = '',
        callable|string|array|null $validate = null,
        callable|string|null $transform = null,
    ): mixed;

    public function multiselect(
        string $label,
        array $options,
        array $default = [],
        bool|string $required = false,
        ?string $scroll = null,
        string $hint = '',
        callable|string|array|null $validate = null,
        callable|string|null $transform = null,
    ): array;

    public function suggest(
        string $label,
        array|Closure $options,
        mixed $default = null,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        string $hint = '',
        callable|string|null $transform = null,
    ): mixed;

    public function search(
        string $label,
        Closure $options,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        ?string $scroll = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): mixed;

    public function multisearch(
        string $label,
        Closure $options,
        bool|string $required = false,
        callable|string|array|null $validate = null,
        string $placeholder = '',
        ?string $scroll = null,
        string $hint = '',
        callable|string|null $transform = null,
    ): array;

    public function pause(string $message = 'Press ENTER to continue'): void;

    public function note(string $message): void;

    public function info(string $message): void;

    public function warning(string $message): void;

    public function error(string $message): void;

    public function alert(string $message): void;

    public function intro(string $message): void;

    public function outro(string $message): void;

    public function table(array $headers, array $rows): void;

    public function spin(
        Closure $callback,
        string $message = '',
    ): mixed;

    public function progress(
        string $label,
        iterable|int $steps,
        ?Closure $callback = null,
        string $hint = '',
    ): Progress|array;

    public function clear(): void;

    public function form(): FormBuilder;
}
