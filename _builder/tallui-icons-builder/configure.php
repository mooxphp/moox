#!/usr/bin/env php
<?php

declare(strict_types=1);

echo '
                            _    _   ___
__ __|   \     |      |    | |  | | |_ _|
   |    _ \    |      |    | |  | |  | |
   |   ___ \   |      |    | |  | |  | |
  _| _/    _\ _____| _____| \____/  |___|

Create a thing with TallUI icon set Builder

Need help scaffolding your package?
See https://github.com/usetall/tallui-icons-builder

';

function ask(string $question, string $default = ''): string
{
    $answer = readline($question.($default ? " ({$default})" : null).': ');

    if (! $answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question.' ('.($default ? 'Y/n' : 'y/N').')');

    if (! $answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line.PHP_EOL;
}

function run(string $command): string
{
    return trim((string) shell_exec($command));
}

function str_after(string $subject, string $search): string
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return substr($subject, $pos + strlen($search));
}

function slugify(string $subject): string
{
    return strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
}

function title_snake(string $subject, string $replace = '_'): string
{
    return str_replace(['-', '_'], $replace, $subject);
}

/** @param  array<mixed>  $replacements*/
function replace_in_file(string $file, array $replacements): void
{
    $contents = (string) file_get_contents($file);

    file_put_contents(
        $file,
        str_replace(
            array_keys($replacements),
            array_values($replacements),
            $contents
        )
    );
}

function remove_prefix(string $prefix, string $content): string
{
    if (str_starts_with($content, $prefix)) {
        return substr($content, strlen($prefix));
    }

    return $content;
}

/** @param  array<mixed>  $names */
function remove_composer_deps(array $names): void
{
    $data = json_decode((string) file_get_contents(__DIR__.'/composer.json'), true);

    foreach ($data['require-dev'] as $name => $version) {
        if (in_array($name, $names, true)) {
            unset($data['require-dev'][$name]);
        }
    }

    file_put_contents(__DIR__.'/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_composer_script(mixed $scriptName): void
{
    $data = json_decode((string) file_get_contents(__DIR__.'/composer.json'), true);

    foreach ($data['scripts'] as $name => $script) {
        if ($scriptName === $name) {
            unset($data['scripts'][$name]);
            break;
        }
    }

    file_put_contents(__DIR__.'/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_readme_paragraphs(string $file): void
{
    $contents = (string) file_get_contents($file);

    file_put_contents(
        $file,
        preg_replace('/<!--delete-->.*<!--\/delete-->/s', '', $contents) ?: $contents
    );
}

function safeUnlink(string $filename): void
{
    if (file_exists($filename) && is_file($filename)) {
        unlink($filename);
    }
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

/** @return array<mixed> */
function replaceForWindows(): array
{
    return (array) preg_split('/\\r\\n|\\r|\\n/', run((string) 'dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename((string) __FILE__).' | findstr /r /i /M /F:/ "TallUIIconsBuilder tallui-icons-builder usetall icons tallui-icons webicons"'));
}

/** @return array<string> */
function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i "tallui|heroicon|icons|tallui-icons" --exclude-dir=vendor ./* ./.github/* | grep -v '.basename(__FILE__)));
}

$gitName = run('git config user.name');
$authorName = ask('Author name', $gitName);

$vendorName = ask('Vendor name', 'usetall');
$vendorSlug = slugify($vendorName);
$vendorNamespace = str_replace('-', '', ucwords($vendorName));
$vendorNamespace = ask('Vendor namespace', $vendorNamespace);

$currentDirectory = getcwd();
$folderName = basename((string) $currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$iconSetName = ask('Icon set name', '');
$icons = slugify(lcfirst($iconSetName));

$className = title_case($packageName);
$className = ask('Class name', $className);

$prefix = ask('Prefix', $icons);

$variableName = lcfirst($className);
$description = ask('Package description', "This is my package {$packageSlug}");

writeln('------');
writeln("Author     : {$vendorSlug} ({$authorName})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln("Icon set : {$iconSetName}");
writeln("Prefix : {$prefix}");
writeln('-----');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (! confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());
foreach ($files as $file) {
    writeln($file);

    replace_in_file($file, [
        'usetall' => $vendorName,
        'Usetall' => ucfirst($vendorName),
        'tallui-icons-builder' => $packageName,
        'A package to easily make use of TallUIIconsBuilder in your Laravel Blade views.' => $description,
        'TallUI Devs' => $authorName,
        'TalluiIconsBuilder' => ucfirst($className),
        'tallui-webicons' => $packageName,
        'webicons' => $prefix,

    ]);

    match (true) {
        str_contains($file, determineSeparator('config/tallui-icons-builder.php')) => rename($file, determineSeparator('./config/'.$packageName.'.php')),
        str_contains($file, determineSeparator('src/TalluiIconsBuilderServiceProvider.php')) => rename($file, determineSeparator('./src/'.ucfirst($className).'ServiceProvider.php')),
        str_contains($file, 'README.md') => remove_readme_paragraphs($file),
        default => [],
    };
}

confirm('Let this script delete itself?', true) && unlink(__FILE__);
