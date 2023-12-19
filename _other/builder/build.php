#!/usr/bin/env php
<?php

declare(strict_types=1);

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
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
}

function title_snake(string $subject, string $replace = '_'): string
{
    return str_replace(['-', '_'], $replace, $subject);
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

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

function replace_readme_paragraphs(string $file, string $content): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        preg_replace('/<!--shortdesc-->.*<!--\/shortdesc-->/s', $content, $contents) ?: $contents
    );
}

function safeUnlink(string $filename)
{
    if (file_exists($filename) && is_file($filename)) {
        unlink($filename);
    }
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename(__FILE__).' | findstr /r /i /M /F:/ "Builder builder create_builder_table"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i "Builder|builder|create_builder_table" --exclude-dir=vendor ./* ./.github/* | grep -v '.basename(__FILE__)));
}

function getGitHubApiEndpoint(string $endpoint): ?stdClass
{
    try {
        $curl = curl_init("https://api.github.com/{$endpoint}");
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: spatie-configure-script/1.0',
            ],
        ]);

        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($statusCode === 200) {
            return json_decode($response);
        }
    } catch (Exception $e) {
        // ignore
    }

    return null;
}

function searchCommitsForGitHubUsername(): string
{
    $authorName = strtolower(trim(shell_exec('git config user.name')));

    $committersRaw = shell_exec("git log --author='@users.noreply.github.com' --pretty='%an:%ae' --reverse");
    $committersLines = explode("\n", $committersRaw);
    $committers = array_filter(array_map(function ($line) use ($authorName) {
        $line = trim($line);
        [$name, $email] = explode(':', $line) + [null, null];

        return [
            'name' => $name,
            'email' => $email,
            'isMatch' => strtolower($name) === $authorName && ! str_contains($name, '[bot]'),
        ];
    }, $committersLines), fn ($item) => $item['isMatch']);

    if (empty($committers)) {
        return '';
    }

    $firstCommitter = reset($committers);

    return explode('@', $firstCommitter['email'])[0] ?? '';
}

function guessGitHubUsernameUsingCli()
{
    try {
        if (preg_match('/ogged in to github\.com as ([a-zA-Z-_]+).+/', shell_exec('gh auth status -h github.com 2>&1'), $matches)) {
            return $matches[1];
        }
    } catch (Exception $e) {
        // ignore
    }

    return '';
}

function guessGitHubUsername(): string
{
    $username = searchCommitsForGitHubUsername();
    if (! empty($username)) {
        return $username;
    }

    $username = guessGitHubUsernameUsingCli();
    if (! empty($username)) {
        return $username;
    }

    // fall back to using the username from the git remote
    $remoteUrl = shell_exec('git config remote.origin.url');
    $remoteUrlParts = explode('/', str_replace(':', '/', trim($remoteUrl)));

    return $remoteUrlParts[1] ?? '';
}

function guessGitHubVendorInfo($authorName, $username): array
{
    $remoteUrl = shell_exec('git config remote.origin.url');
    $remoteUrlParts = explode('/', str_replace(':', '/', trim($remoteUrl)));

    $response = getGitHubApiEndpoint("orgs/{$remoteUrlParts[1]}");

    if ($response === null) {
        return [$authorName, $username];
    }

    return [$response->name ?? $authorName, $response->login ?? $username];
}

writeln(' ');
writeln(' ');
writeln('▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ▓▓▓▓▓▓▓▓▓▓▓       ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓   ▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓');
writeln('▓▓▒░░▒▓▓▒▒░░░░░░▒▒▓▓▓▒░░░░░░░▒▓▓   ▓▓▓▓▒░░░░░░░▒▓▓▓▓     ▓▓▓▓▓▒░░░░░░░▒▒▓▓▓▓▓▒▒▒▒▓▓      ▓▓▓▒▒▒▒▓▓');
writeln('▓▒░░░░░░░░░░░░░░░░░░░░░░░░░░░░░▓▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓░░░░░▒▓▓   ▓▓▒░░░░░▓▓');
writeln('▓▒░░░░░░▒▓▓▓▓▒░░░░░░░▒▓▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓▓▓▒░░░░░░░▓▓▓▓░░░░░░▒▓▓▓▓▓░░░░░░▒▓▓░░░░░▒▓▓▓▓▓░░░░░▒▓▓');
writeln('▓▒░░░░▓▓▓▓  ▓▓░░░░░▓▓▓  ▓▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▓░░░░░▓░░░░░░▓▓▓▓   ▓▓▓▒░░░░▓▓▓▒░░░░░▓▓▓░░░░░▓▓▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▓░░▒░░░░░▓▓▓        ▓▓░░░░▒▓▓▓▓░░░░░░░░░░░▓▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓          ▓▓▓░░░░░▒▓▓          ▓▓▒░░░░▓ ▓▓▓░░░░░░░░░▓▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▒░░░░░▒░░▒▓▓        ▓▓░░░░▒▓▓▓▒░░░░░▒░░░░░▒▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▒░░░░░▒▒░░░░░▒▓▓▓   ▓▓▓░░░░░▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓░░░░░░▒▒▓▓▒░░░░░░▒▓▓▓▓░░░░░░░▒▒▓▓▒░░░░░░▓▓▓░░░░░▒▓▓▓▓▓▒░░░░░▓▓');
writeln('▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▒░░░░░▓▓▓   ▓▓▒░░░░░▒▓');
writeln('▓▓░░░▒▓▓    ▓▓▒░░░▒▓▓    ▓▓░░░░▓▓  ▓▓▓▓▒░░░░░░▒▒▓▓▓▓     ▓▓▓▓▓▒▒░░░░░▒▒▓▓▓▓▓░░░░▒▓▓      ▓▓▓░░░░▒▓');
writeln('▓▓▓▓▓▓▓      ▓▓▓▓▓▓▓     ▓▓▓▓▓▓▓▓    ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓▓');
writeln(' ');
writeln(' ');
writeln('Welcome to Moox Builder');
writeln(' ');
writeln('This script will guide you through the process of configuring your own Moox package.');
writeln(' ');

$authorName = ask('Author name', 'Moox Developer');

$authorEmail = ask('Author email', 'dev@moox.org');

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$className = title_case($packageName);
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', "This is my package {$packageSlug}");

writeln('------');
writeln("Author : {$authorName}");
writeln("Author Email : {$authorEmail}");
writeln("Namespace  : Moox\\{$className}");
writeln("Packagename : moox\\{$packageSlug}");
writeln("Class name : {$className}Plugin");
writeln('------');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (! confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        'Moox Developer' => $authorName,
        'dev@moox.org' => $authorEmail,
        'Builder' => $className,
        'builder' => $packageSlug,
        'create_builder_table' => title_snake($packageSlug),
        'This template is used for generating all Moox packages.' => $description,
        'Here are some things missing, like an overview with screenshots about this package, or simply a link to the package\'s docs.' => $description,
    ]);

    match (true) {
        str_contains($file, determineSeparator('src/BuilderPlugin.php')) => rename($file, determineSeparator('./src/'.$className.'Plugin.php')),
        str_contains($file, determineSeparator('src/BuilderServiceProvider.php')) => rename($file, determineSeparator('./src/'.$className.'ServiceProvider.php')),
        str_contains($file, determineSeparator('src/Resources/BuilderResource.php')) => rename($file, determineSeparator('./src/Resources/'.$className.'Resource.php')),
        str_contains($file, determineSeparator('src/Models/Builder.php')) => rename($file, determineSeparator('./src/Models/'.$className.'.php')),
        str_contains($file, determineSeparator('src/Resources/BuilderResource/Widgets/BuilderWidgets.php')) => rename($file, determineSeparator('./src/Resources/BuilderResource/Widgets/'.$className.'Widgets.php')),
        str_contains($file, determineSeparator('database/migrations/create_builder_table.php.stub')) => rename($file, determineSeparator('./database/migrations/create_'.title_snake($packageSlugWithoutPrefix).'_table.php.stub')),
        str_contains($file, determineSeparator('config/builder.php')) => rename($file, determineSeparator('./config/'.$packageSlugWithoutPrefix.'.php')),
        str_contains($file, 'README.md') => replace_readme_paragraphs($file, $description),
        default => [],
    };
}
rename(determineSeparator('src/Resources/BuilderResource'), determineSeparator('./src/Resources/'.$className.'Resource'));

confirm('Execute `composer install` and run tests?') && run('composer install && composer test');

confirm('Let this script delete itself?', true) && unlink(__FILE__);

writeln(' ');
writeln('Moox Builder is finished. Have fun!');
