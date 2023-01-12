#!/usr/bin/env php
<?php

declare(strict_types=1);

echo '
                            _    _   ___
__ __|   \     |      |    | |  | | |_ _|
   |    _ \    |      |    | |  | |  | |
   |   ___ \   |      |    | |  | |  | |
  _| _/    _\ _____| _____| \____/  |___|

Create a thing with TallUI Package Builder

Need help scaffolding your package?
See https://tallui.io/package-builder

';

function ask(string $question, string $default = ''): string
{
    $answer = readline($question . ($default ? " ({$default})" : null) . ': ');

    if (!$answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question . ' (' . ($default ? 'Y/n' : 'y/N') . ')');

    if (!$answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line . PHP_EOL;
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
    $data = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

    foreach ($data['require-dev'] as $name => $version) {
        if (in_array($name, $names, true)) {
            unset($data['require-dev'][$name]);
        }
    }

    file_put_contents(__DIR__ . '/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_composer_script(mixed $scriptName): void
{
    $data = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);

    foreach ($data['scripts'] as $name => $script) {
        if ($scriptName === $name) {
            unset($data['scripts'][$name]);
            break;
        }
    }

    file_put_contents(__DIR__ . '/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
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
    return (array) preg_split('/\\r\\n|\\r|\\n/', run((string) 'dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i ' . basename((string) __FILE__) . ' | findstr /r /i /M /F:/ ":author :vendor :package Usetall skeleton tallui_package_builder vendor_name vendor_slug builder@tallui.io"'));
}

/** @return array<string> */
function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i ":author|:vendor|:package|Usetall|skeleton|tallui_package_builder|vendor_name|vendor_slug|builder@tallui.io" --exclude-dir=vendor ./* ./.github/* | grep -v ' . basename(__FILE__)));
}

$gitName = run('git config user.name');
$authorName = ask('Author name', $gitName);

$gitEmail = run('git config user.email');
$authorEmail = ask('Author email', $gitEmail);

$usernameGuess = explode(':', run('git config remote.origin.url'))[1];
$usernameGuess = dirname($usernameGuess);
$usernameGuess = basename($usernameGuess);
$authorUsername = ask('Author username', $usernameGuess);

$vendorName = ask('Vendor name', $authorUsername);
$vendorSlug = slugify($vendorName);
$vendorNamespace = str_replace('-', '', ucwords($vendorName));
$vendorNamespace = ask('Vendor namespace', $vendorNamespace);

$currentDirectory = getcwd();
$folderName = basename((string) $currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$className = title_case($packageName);
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', "This is my package {$packageSlug}");

$usePhpStan = confirm('Enable PhpStan?', true);
$useLaravelPint = confirm('Enable Laravel Pint?', true);
$useDependabot = confirm('Enable Dependabot?', true);
$useLaravelRay = confirm('Use Ray for debugging?', true);
$useUpdateChangelogWorkflow = confirm('Use automatic changelog updater workflow?', true);

writeln('------');
writeln("Author     : {$authorName} ({$authorUsername}, {$authorEmail})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln('---');
writeln('Packages & Utilities');
writeln('Use Laravel/Pint       : ' . ($useLaravelPint ? 'yes' : 'no'));
writeln('Use Larastan/PhpStan : ' . ($usePhpStan ? 'yes' : 'no'));
writeln('Use Dependabot       : ' . ($useDependabot ? 'yes' : 'no'));
writeln('Use Ray App          : ' . ($useLaravelRay ? 'yes' : 'no'));
writeln('Use Auto-Changelog   : ' . ($useUpdateChangelogWorkflow ? 'yes' : 'no'));
writeln('------');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (!confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        'Builder_Fullname' => $authorName,
        'Builder_Username' => $authorUsername,
        'builder@tallui.io' => $authorEmail,
        'TallUI_Devs' => $vendorName,
        'tallui_package_builder' => $vendorSlug,
        'Usetall' => $vendorNamespace,
        'TallUI Package Builder' => $packageName,
        'tallui-package-builder' => $packageSlug,
        'package_slug_without_prefix' => $packageSlugWithoutPrefix,
        'TalluiPackageBuilder' => $className,
        'tallui_package_builder' => title_snake($packageSlug),
        'variable' => $variableName,
        'This is the TallUI package builder' => $description,
    ]);

    match (true) {
        str_contains($file, determineSeparator('src/TalluiPackageBuilder.php')) => rename($file, determineSeparator('./src/' . $className . '.php')),
        str_contains($file, determineSeparator('src/TalluiPackageBuilderServiceProvider.php')) => rename($file, determineSeparator('./src/' . $className . 'ServiceProvider.php')),
        str_contains($file, determineSeparator('src/Facades/TalluiPackageBuilder.php')) => rename($file, determineSeparator('./src/Facades/' . $className . '.php')),
        str_contains($file, determineSeparator('src/Commands/TalluiPackageBuilderCommand.php')) => rename($file, determineSeparator('./src/Commands/' . $className . 'Command.php')),
        str_contains($file, determineSeparator('database/migrations/create_skeleton_table.php.stub')) => rename($file, determineSeparator('./database/migrations/create_' . title_snake($packageSlugWithoutPrefix) . '_table.php.stub')),
        str_contains($file, determineSeparator('config/skeleton.php')) => rename($file, determineSeparator('./config/' . $packageSlugWithoutPrefix . '.php')),
        str_contains($file, 'README.md') => remove_readme_paragraphs($file),
        default => [],
    };
}

if (!$useLaravelPint) {
    safeUnlink(__DIR__ . '/.github/workflows/fix-php-code-style-issues.yml');
    safeUnlink(__DIR__ . '/pint.json');
}

if (!$usePhpStan) {
    safeUnlink(__DIR__ . '/phpstan.neon.dist');
    safeUnlink(__DIR__ . '/phpstan-baseline.neon');
    safeUnlink(__DIR__ . '/.github/workflows/phpstan.yml');

    remove_composer_deps([
        'phpstan/extension-installer',
        'phpstan/phpstan-deprecation-rules',
        'phpstan/phpstan-phpunit',
        'nunomaduro/larastan',
    ]);

    remove_composer_script('phpstan');
}

if (!$useDependabot) {
    safeUnlink(__DIR__ . '/.github/dependabot.yml');
    safeUnlink(__DIR__ . '/.github/workflows/dependabot-auto-merge.yml');
}

if (!$useLaravelRay) {
    remove_composer_deps(['spatie/laravel-ray']);
}

if (!$useUpdateChangelogWorkflow) {
    safeUnlink(__DIR__ . '/.github/workflows/update-changelog.yml');
}

confirm('Execute `composer install` and run tests?') && run('composer install && composer test');

confirm('Let this script delete itself?', true) && unlink(__FILE__);
