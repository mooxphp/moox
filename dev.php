<?php

declare(strict_types=1);

$options = getopt('', [
    'laravel::',
    'db::',
    'delete',
    'help',
]);

if (isset($options['help'])) {
    echo <<<'TXT'
Moox Dev App Bootstrapper

Usage:
  php dev.php
  php dev.php --laravel=13 --db=postgresql
  php dev.php --delete

Options:
  --laravel=VERSION   Laravel major version. Default: 13
  --db=DRIVER         Database: mysql, postgresql, sqlite (without flag: asked after skeleton)
  --delete            Delete generated Laravel app files from repo root
  --help              Show this help

Env templates: .env.mysql, .env.postgresql, .env.sqlite → copied to .env

TXT;
    exit(0);
}

$laravelVersion = $options['laravel'] ?? '13';
$delete = isset($options['delete']);
$dbOption = $options['db'] ?? null;

$root = __DIR__;

$allowedDatabases = ['mysql', 'postgresql', 'sqlite'];

$appPaths = [
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'storage',
    'tests',
    'vendor',
    'composer.json',
    'composer.lock',
    '.env',
    'artisan',
    'package.json',
    'phpunit.xml',
    'vite.config.js',
];

function run(string $command): void
{
    echo "▶ {$command}\n";

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        echo "❌ Command failed with exit code {$exitCode}\n";
        exit($exitCode);
    }
}

function removePath(string $path): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }

    if (is_dir($path) && ! is_link($path)) {
        run('rm -rf '.escapeshellarg($path));

        return;
    }

    unlink($path);
}

function resolveDatabase(?string $dbOption): string
{
    global $allowedDatabases;

    if ($dbOption !== null) {
        if (! in_array($dbOption, $allowedDatabases, true)) {
            echo "❌ Invalid --db={$dbOption}. Use: ".implode(', ', $allowedDatabases)."\n";
            exit(1);
        }

        return $dbOption;
    }

    if (! stream_isatty(STDIN)) {
        echo "❌ --db=mysql|postgresql|sqlite ist in CI/non-interaktiv erforderlich.\n";
        exit(1);
    }

    echo "Welche Datenbank soll verwendet werden?\n";
    echo "  1) mysql\n";
    echo "  2) postgresql\n";
    echo "  3) sqlite\n";

    $choiceMap = [
        '1' => 'mysql',
        '2' => 'postgresql',
        '3' => 'sqlite',
    ];

    while (true) {
        echo 'Auswahl (1-3): ';
        $input = trim(fgets(STDIN) ?: '');

        if (isset($choiceMap[$input])) {
            return $choiceMap[$input];
        }

        if (in_array($input, $allowedDatabases, true)) {
            return $input;
        }

        echo "Bitte 1, 2 oder 3 eingeben.\n";
    }
}

function installEnv(string $root, string $database): void
{
    $source = $root.DIRECTORY_SEPARATOR.'.env.'.$database;

    if (! is_readable($source)) {
        echo "❌ Env template not found: {$source}\n";
        exit(1);
    }

    $target = $root.DIRECTORY_SEPARATOR.'.env';

    if (! copy($source, $target)) {
        echo "❌ Failed to copy {$source} to .env\n";
        exit(1);
    }

    echo "✅ .env created from .env.{$database}\n";
}

function buildComposerJson(string $root, string $laravelVersion): void
{
    putenv('APP_ENV=development');
    putenv('COMPOSER_NO_DEV=0');

    if (! function_exists('env')) {
        function env(string $key, mixed $default = null): mixed
        {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

            if ($value === null && file_exists(__DIR__.'/.env')) {
                foreach (file(__DIR__.'/.env') as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        [$k, $v] = array_map('trim', $parts);
                        $_ENV[$k] = $_SERVER[$k] = $v;
                    }
                }
                $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }

            return $value ?? $default;
        }
    }

    $config = require $root.'/packages/devlink/config/devlink.php';

    $composer = [
        'name' => 'moox/dev-app',
        'type' => 'project',
        'require' => [
            'laravel/framework' => '^'.$laravelVersion.'.0',
        ],
        'autoload' => [
            'psr-4' => [
                'App\\' => 'app/',
            ],
        ],
        'repositories' => [],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
        'config' => [
            'allow-plugins' => [
                'pestphp/pest-plugin' => true,
            ],
        ],
        'autoload-dev' => [
            'psr-4' => [
                'Tests\\' => 'tests/',
            ],
        ],
    ];

    foreach ($config['packages'] as $name => $pkg) {
        if (! ($pkg['active'] ?? false)) {
            continue;
        }

        if (! isset($pkg['type']) || ! in_array($pkg['type'], ['local', 'public'], true)) {
            continue;
        }

        $pkgPath = $pkg['path'] ?? null;
        $pkgPath = str_replace('../moox/', '', (string) $pkgPath);

        if (! $pkgPath || ! is_dir($pkgPath)) {
            continue;
        }

        if (! file_exists($pkgPath.'/composer.json')) {
            continue;
        }

        $target = ($pkg['dev'] ?? false) ? 'require-dev' : 'require';
        $composer[$target]["moox/{$name}"] = '*';

        $composer['repositories'][] = [
            'type' => 'path',
            'url' => $pkgPath,
            'options' => ['symlink' => false],
        ];
    }

    file_put_contents(
        $root.'/composer.json',
        json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

if ($delete) {
    foreach ($appPaths as $path) {
        removePath($root.DIRECTORY_SEPARATOR.$path);
    }

    echo "✅ Generated Laravel app files deleted.\n";
    exit(0);
}

echo "🚀 Building Moox dev app with Laravel {$laravelVersion}\n";

$tempDir = $root.DIRECTORY_SEPARATOR.'laravel-temp';

removePath($tempDir);

run('composer create-project laravel/laravel '.escapeshellarg($tempDir).' "^'.$laravelVersion.'.0" --no-install --no-scripts');

foreach (scandir($tempDir) ?: [] as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }

    $source = $tempDir.DIRECTORY_SEPARATOR.$item;
    $target = $root.DIRECTORY_SEPARATOR.$item;

    if (file_exists($target)) {
        continue;
    }

    rename($source, $target);
}

removePath($tempDir);

$database = resolveDatabase(is_string($dbOption) ? $dbOption : null);

installEnv($root, $database);

buildComposerJson($root, $laravelVersion);

run('composer update --no-interaction --prefer-dist');

if (! file_exists($root.'/vendor/phpstan/phpstan/phpstan')) {
    echo "❌ PHPStan binary not found.\n";
    exit(1);
}

if (! file_exists($root.'/vendor/pestphp/pest/bin/pest')) {
    echo "❌ Pest binary not found.\n";
    exit(1);
}

if ($database === 'sqlite') {
    $sqlitePath = $root.'/database/database.sqlite';
    if (! is_dir(dirname($sqlitePath))) {
        mkdir(dirname($sqlitePath), 0755, true);
    }
    if (! file_exists($sqlitePath)) {
        touch($sqlitePath);
    }
}

echo "✅ Moox is ready.\n";
