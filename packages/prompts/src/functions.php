<?php

namespace Moox\Prompts;

use Closure;
use Laravel\Prompts\FormBuilder;
use Laravel\Prompts\Progress;
use Moox\Prompts\Support\PromptRuntime;

function text(
    string $label,
    string $placeholder = '',
    mixed $default = null,
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $hint = '',
    callable|string|null $transform = null,
): string {
    return app(PromptRuntime::class)->text(
        $label, $placeholder, $default, $required,
        $validate, $hint, $transform
    );
}

function textarea(
    string $label,
    string $placeholder = '',
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $hint = '',
    callable|string|null $transform = null,
): string {
    return app(PromptRuntime::class)->textarea(
        $label, $placeholder, $required, $validate, $hint, $transform
    );
}

function password(
    string $label,
    string $placeholder = '',
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $hint = '',
    callable|string|null $transform = null,
): string {
    return app(PromptRuntime::class)->password(
        $label, $placeholder, $required, $validate, $hint, $transform
    );
}

function confirm(
    string $label,
    bool $default = false,
    bool|string $required = false,
    string $yes = 'I accept',
    string $no = 'I decline',
    string $hint = '',
): bool {
    return app(PromptRuntime::class)->confirm(
        $label, $default, $required, $yes, $no, $hint
    );
}

function select(
    string $label,
    array $options,
    mixed $default = null,
    ?string $scroll = null,
    string $hint = '',
    callable|string|array|null $validate = null,
    callable|string|null $transform = null,
): mixed {
    return app(PromptRuntime::class)->select(
        $label, $options, $default,
        $scroll, $hint, $validate, $transform
    );
}

function multiselect(
    string $label,
    array $options,
    array $default = [],
    bool|string $required = false,
    ?string $scroll = null,
    string $hint = '',
    callable|string|array|null $validate = null,
    callable|string|null $transform = null,
): array {
    return app(PromptRuntime::class)->multiselect(
        $label, $options, $default, $required,
        $scroll, $hint, $validate, $transform
    );
}

function suggest(
    string $label,
    array|Closure $options,
    mixed $default = null,
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $placeholder = '',
    string $hint = '',
    callable|string|null $transform = null,
): mixed {
    return app(PromptRuntime::class)->suggest(
        $label, $options, $default, $required,
        $validate, $placeholder, $hint, $transform
    );
}

function search(
    string $label,
    Closure $options,
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $placeholder = '',
    ?string $scroll = null,
    string $hint = '',
    callable|string|null $transform = null,
): mixed {
    return app(PromptRuntime::class)->search(
        $label, $options, $required,
        $validate, $placeholder, $scroll, $hint, $transform
    );
}

function multisearch(
    string $label,
    Closure $options,
    bool|string $required = false,
    callable|string|array|null $validate = null,
    string $placeholder = '',
    ?string $scroll = null,
    string $hint = '',
    callable|string|null $transform = null,
): array {
    return app(PromptRuntime::class)->multisearch(
        $label, $options, $required,
        $validate, $placeholder, $scroll, $hint, $transform
    );
}

function pause(string $message = 'Press ENTER to continue'): void
{
    app(PromptRuntime::class)->pause($message);
}

function note(string $message): void
{
    app(PromptRuntime::class)->note($message);
}

function info(string $message): void
{
    app(PromptRuntime::class)->info($message);
}

function warning(string $message): void
{
    app(PromptRuntime::class)->warning($message);
}

function error(string $message): void
{
    app(PromptRuntime::class)->error($message);
}

function alert(string $message): void
{
    app(PromptRuntime::class)->alert($message);
}

function intro(string $message): void
{
    app(PromptRuntime::class)->intro($message);
}

function outro(string $message): void
{
    app(PromptRuntime::class)->outro($message);
}

function table(array $headers, array $rows): void
{
    app(PromptRuntime::class)->table($headers, $rows);
}

function spin(Closure $callback, string $message = ''): mixed
{
    return app(PromptRuntime::class)->spin($callback, $message);
}

function progress(
    string $label,
    iterable|int $steps,
    ?Closure $callback = null,
    string $hint = '',
): Progress|array {
    return app(PromptRuntime::class)->progress(
        $label, $steps, $callback, $hint
    );
}

function clear(): void
{
    app(PromptRuntime::class)->clear();
}

function form(): FormBuilder
{
    return app(PromptRuntime::class)->form();
}
