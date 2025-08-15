<?php

declare(strict_types=1);

function ask(string $question, string $default = ''): string
{
    $answer = readline($question.($default !== '' && $default !== '0' ? sprintf(' (%s)', $default) : null).': ');

    if (! $answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question.' ('.($default ? 'Y/n' : 'y/N').')');

    if ($answer === '' || $answer === '0') {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function isValidPackageName($packageName): bool
{
    if (empty($packageName)) {
        return false;
    }

    $reservedName = 'skeleton';

    return ! str_contains(strtolower((string) $packageName), $reservedName);
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

function replace_readme_sections(string $file, array $sections): void
{
    $contents = file_get_contents($file);

    foreach ($sections as $sectionName => $content) {
        $startComment = '<!-- '.$sectionName.' -->';
        $endComment = '<!-- /'.$sectionName.' -->';

        $pattern = '/'.preg_quote($startComment, '/').'.*?'.preg_quote($endComment, '/').'/s';

        $contents = preg_replace($pattern, $startComment.PHP_EOL.PHP_EOL.$content.PHP_EOL.PHP_EOL.$endComment, $contents) ?: $contents;
    }

    file_put_contents($file, $contents);
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

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename(__FILE__).' | findstr /r /i /M /F:/ "Skeleton skeleton"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i "Skeleton|skeleton" --exclude-dir=vendor ./* | grep -v '.basename(__FILE__)));
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
writeln('This script will guide you through the process of building your own Moox package.');
writeln(' ');

$authorName = ask('Author name', 'Moox Developer');

$authorEmail = ask('Author email', 'dev@moox.org');

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

if (! isValidPackageName($folderName)) {
    do {
        writeln('Invalid package name: "skeleton" is not allowed.');
        $packageName = ask('Package name');
    } while (! isValidPackageName($packageName));
} else {
    $packageName = $folderName;
}

$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$className = title_case($packageName);
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', ucfirst($packageName).' is a new package made with Moox.');

$features = ask('Package features comma separated', 'New Moox Package, Does awesome stuff');

$usage = ask('Package usage instructions', 'Install the package and see what it does.');

writeln('------');
writeln('Author : '.$authorName);
writeln('Author Email : '.$authorEmail);
writeln('Namespace  : Moox\\'.$className);
writeln('Packagename : moox\\'.$packageSlug);
writeln(sprintf('Class name : %sPlugin', $className));
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
        'Skeleton' => $className,
        'skeleton' => $packageSlug,
    ]);

    match (true) {
        str_contains((string) $file, determineSeparator('src/SkeletonServiceProvider.php')) => rename($file, determineSeparator('./src/'.$className.'ServiceProvider.php')),
        str_contains((string) $file, 'README.md') => replace_readme_sections($file, [
            'Description' => $description,
            'Features' => '- '.implode(PHP_EOL.'- ', array_map('trim', explode(',', $features))),
            'Usage' => $usage,
        ]),
        default => [],
    };
}

unlink(determineSeparator('banner.jpg'));
rename(determineSeparator('banner_build.jpg'), determineSeparator('banner.jpg'));
unlink(determineSeparator('screenshot/main.jpg'));
rename(determineSeparator('screenshot/main_build.jpg'), determineSeparator('screenshot/main.jpg'));
rename(determineSeparator('config/skeleton.php'), determineSeparator('./config/'.$packageSlugWithoutPrefix.'.php'));

if (confirm('Execute `composer install` and run tests?')) {
    run('composer install && composer test');
}

if (confirm('Let this script delete itself?', true)) {
    unlink(__FILE__);
}

writeln(' ');
writeln('Moox Builder has finished. Have fun!');
