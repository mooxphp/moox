<?php

declare(strict_types=1);
use Moox\Devlink\Support\DevlinkPackageNotRegisteredException;
use Moox\Devlink\Support\EffectivePackages;

$options = getopt('', [
    'laravel::',
    'db::',
    'delete',
    'help',
]);

if (isset($options['help'])) {
    echo <<<'TXT'
moox Dev App Bootstrapper

Usage:
  php dev.php
  php dev.php --laravel=13 --db=postgresql
  php dev.php --delete

Options:
  --laravel=VERSION   Laravel major version. Default: 13
  --db=DRIVER         Database: mysql, postgresql, sqlite (without flag: asked after skeleton)
  --delete            Delete generated Laravel app files from repo root (asks to drop DB)
  --help              Show this help

Env templates: .env.mysql, .env.postgresql, .env.sqlite → copied to .env
Devlink: packages/devlink/config/devlink.php → config/devlink.php (optional edit before composer)

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
    'phpunit.cache',
    'phpstan',
    'build',
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

function isAbsolutePath(string $path): bool
{
    if ($path === '') {
        return false;
    }

    if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        return true;
    }

    return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
}

function removePath(string $path): void
{
    if (! file_exists($path) && ! is_link($path)) {
        return;
    }

    echo "🗑️  Removing {$path}\n";

    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (PHP_OS_FAMILY === 'Windows') {
        // Symlink/File

        if (is_link($normalized) || is_file($normalized)) {
            exec(

                'cmd /c del /f /q '.escapeshellarg($normalized).' 2>NUL',

                $output,

                $exitCode

            );
        } else {
            // Directory

            exec(

                'cmd /c rmdir /s /q '.escapeshellarg($normalized).' 2>NUL',

                $output,

                $exitCode

            );
        }
    } else {
        exec(

            'rm -rf '.escapeshellarg($normalized).' 2>/dev/null',

            $output,

            $exitCode

        );
    }

    if (file_exists($normalized) || is_link($normalized)) {
        throw new RuntimeException("Failed to remove path: {$normalized}");
    }
}

function parseDotenv(string $path): array
{
    if (! is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $vars = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (! str_contains($line, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);

        if ($k === '') {
            continue;
        }

        if (
            (str_starts_with($v, '"') && str_ends_with($v, '"') && strlen($v) >= 2)
            || (str_starts_with($v, "'") && str_ends_with($v, "'") && strlen($v) >= 2)
        ) {
            $v = stripcslashes(substr($v, 1, -1));
        }

        $vars[$k] = $v;
    }

    return $vars;
}

function promptYesNo(string $question, bool $defaultYes): bool
{
    $hint = $defaultYes ? 'Y/n' : 'y/N';

    while (true) {
        echo "{$question} [{$hint}] ";
        $raw = fgets(STDIN);
        $input = strtolower(trim($raw === false ? '' : $raw));

        if ($input === '') {
            return $defaultYes;
        }

        if (in_array($input, ['y', 'yes', 'j', 'ja', '1'], true)) {
            return true;
        }

        if (in_array($input, ['n', 'no', '0'], true)) {
            return false;
        }

        echo "Please enter y or n.\n";
    }
}

function requireInteractiveForPrompts(): void
{
    if (! stream_isatty(STDIN)) {
        echo "❌ Interactive input is not available (no TTY).\n";
        exit(1);
    }
}

function appLooksPresent(string $root): bool
{
    return file_exists($root.DIRECTORY_SEPARATOR.'artisan')
        && file_exists($root.DIRECTORY_SEPARATOR.'composer.json');
}

function deleteAppFiles(string $root, array $appPaths): void
{
    foreach ($appPaths as $path) {
        removePath($root.DIRECTORY_SEPARATOR.$path);
    }
}

function dropDatabaseFromEnv(string $root, string $envPath): void
{
    $vars = parseDotenv($envPath);
    $connection = strtolower((string) ($vars['DB_CONNECTION'] ?? ''));

    if ($connection === 'sqlite') {
        $dbFile = (string) ($vars['DB_DATABASE'] ?? '');
        if ($dbFile === '') {
            echo "❌ DB_DATABASE is missing in .env.\n";
            exit(1);
        }

        $full = isAbsolutePath($dbFile)
            ? $dbFile
            : $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dbFile);

        if (file_exists($full) || is_link($full)) {
            unlink($full);
            echo "✅ SQLite database file deleted: {$full}\n";
        } else {
            echo "ℹ️ SQLite database file not found: {$full}\n";
        }

        return;
    }

    if (in_array($connection, ['mysql', 'mariadb'], true)) {
        $host = (string) ($vars['DB_HOST'] ?? '127.0.0.1');
        $port = (int) ($vars['DB_PORT'] ?? 3306);
        $database = (string) ($vars['DB_DATABASE'] ?? '');
        $user = (string) ($vars['DB_USERNAME'] ?? 'root');
        $password = (string) ($vars['DB_PASSWORD'] ?? '');

        if ($database === '') {
            echo "❌ DB_DATABASE is missing in .env.\n";
            exit(1);
        }

        if (! extension_loaded('pdo_mysql')) {
            echo "❌ pdo_mysql extension is required to drop MySQL databases.\n";
            exit(1);
        }

        $dsn = 'mysql:host='.$host.';port='.$port;
        try {
            $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $quoted = str_replace('`', '``', $database);
            $pdo->exec('DROP DATABASE IF EXISTS `'.$quoted.'`');
            echo "✅ MySQL database dropped: {$database}\n";
        } catch (PDOException $e) {
            echo '❌ MySQL DROP DATABASE failed: '.$e->getMessage()."\n";
            exit(1);
        }

        return;
    }

    if (in_array($connection, ['pgsql', 'postgres', 'postgresql'], true)) {
        $host = (string) ($vars['DB_HOST'] ?? '127.0.0.1');
        $port = (int) ($vars['DB_PORT'] ?? 5432);
        $database = (string) ($vars['DB_DATABASE'] ?? '');
        $user = (string) ($vars['DB_USERNAME'] ?? 'postgres');
        $password = (string) ($vars['DB_PASSWORD'] ?? '');

        if ($database === '') {
            echo "❌ DB_DATABASE is missing in .env.\n";
            exit(1);
        }

        if (! extension_loaded('pdo_pgsql')) {
            echo "❌ pdo_pgsql extension is required to drop PostgreSQL databases.\n";
            exit(1);
        }

        $dsn = 'pgsql:host='.$host.';port='.$port.';dbname=postgres';
        try {
            $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $safe = str_replace('"', '""', $database);
            $pdo->exec('DROP DATABASE IF EXISTS "'.$safe.'" WITH (FORCE)');
            echo "✅ PostgreSQL database dropped: {$database}\n";
        } catch (PDOException $e) {
            echo '❌ PostgreSQL DROP DATABASE failed: '.$e->getMessage()."\n";
            exit(1);
        }

        return;
    }

    echo "❌ Unknown DB_CONNECTION in .env: {$connection}\n";
    exit(1);
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
        echo "❌ --db=mysql|postgresql|sqlite is required for non-interactive/CI use.\n";
        exit(1);
    }

    echo "Which database driver should be used?\n";
    echo "  1) mysql\n";
    echo "  2) postgresql\n";
    echo "  3) sqlite\n";

    $choiceMap = [
        '1' => 'mysql',
        '2' => 'postgresql',
        '3' => 'sqlite',
    ];

    while (true) {
        echo 'Choice (1-3): ';
        $input = trim(fgets(STDIN) ?: '');

        if (isset($choiceMap[$input])) {
            return $choiceMap[$input];
        }

        if (in_array($input, $allowedDatabases, true)) {
            return $input;
        }

        echo "Please enter 1, 2, or 3.\n";
    }
}

function databaseDriverFromEnvFile(string $root): string
{
    $path = $root.DIRECTORY_SEPARATOR.'.env';

    if (! is_readable($path)) {
        return 'mysql';
    }

    $vars = parseDotenv($path);
    $c = strtolower((string) ($vars['DB_CONNECTION'] ?? 'mysql'));

    if ($c === 'sqlite') {
        return 'sqlite';
    }

    if (in_array($c, ['pgsql', 'postgres', 'postgresql'], true)) {
        return 'postgresql';
    }

    return 'mysql';
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

function publishDevlinkConfig(string $root): void
{
    $source = $root.DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'devlink'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'devlink.php';

    if (! is_readable($source)) {
        echo "❌ Devlink package config not found: {$source}\n";
        exit(1);
    }

    $targetDir = $root.DIRECTORY_SEPARATOR.'config';

    if (! is_dir($targetDir)) {
        if (! mkdir($targetDir, 0755, true)) {
            echo "❌ Failed to create directory: {$targetDir}\n";
            exit(1);
        }
    }

    $target = $targetDir.DIRECTORY_SEPARATOR.'devlink.php';

    if (! copy($source, $target)) {
        echo "❌ Failed to publish devlink config to {$target}\n";
        exit(1);
    }

    echo "✅ config/devlink.php published.\n";
}

function offerDevlinkConfigPause(string $root): void
{
    if (! stream_isatty(STDIN)) {
        return;
    }

    $path = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'devlink.php';

    if (! file_exists($path)) {
        return;
    }

    if (! promptYesNo('Edit config/devlink.php before composer update?', true)) {
        return;
    }

    echo "Edit {$path}, then press Enter when done.\n";
    fgets(STDIN);
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

    $configPath = $root.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'devlink.php';

    if (! is_readable($configPath)) {
        echo "❌ config/devlink.php not found or not readable: {$configPath}\n";
        exit(1);
    }

    $config = require $configPath;

    require_once $root.'/packages/devlink/src/Support/DevlinkPackageNotRegisteredException.php';
    require_once $root.'/packages/devlink/src/Support/EffectivePackages.php';

    try {
        $effectivePackages = EffectivePackages::resolve($root, $config['packages']);
    } catch (DevlinkPackageNotRegisteredException $e) {
        echo '❌ '.$e->getMessage()."\n";
        exit(1);
    }

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

    foreach ($effectivePackages as $name => $pkg) {
        $type = (string) ($pkg['type'] ?? '');

        if (! EffectivePackages::isLinkableType($type)) {
            continue;
        }

        $directory = EffectivePackages::resolvePackageDirectory($root, $pkg);

        if ($directory === null || ! is_file($directory.DIRECTORY_SEPARATOR.'composer.json')) {
            continue;
        }

        $pkgPath = str_replace('../moox/', '', (string) ($pkg['path'] ?? ''));
        $pkgPath = str_replace('\\', '/', $pkgPath);

        if ($pkgPath === '') {
            continue;
        }

        $target = ($pkg['dev'] ?? false) ? 'require-dev' : 'require';
        $composer[$target]["moox/{$name}"] = '*';

        $composer['repositories'][] = [
            'type' => 'path',
            'url' => $pkgPath,
            'options' => ['symlink' => true],
        ];
    }

    file_put_contents(
        $root.'/composer.json',
        json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

$envPath = $root.DIRECTORY_SEPARATOR.'.env';

if ($delete) {
    $dropDb = false;

    if (file_exists($envPath)) {
        if (stream_isatty(STDIN)) {
            $dropDb = promptYesNo('Drop database?', true);
        } else {
            echo "ℹ️ Non-interactive: database will not be dropped (no TTY).\n";
        }

        if ($dropDb) {
            dropDatabaseFromEnv($root, $envPath);
        }
    }

    deleteAppFiles($root, $appPaths);

    echo "✅ Generated Laravel app files deleted.\n";
    exit(0);
}

$keepExistingApp = false;

if (appLooksPresent($root)) {
    requireInteractiveForPrompts();

    $delApp = promptYesNo('An app already exists in this repo. Delete app files?', false);

    if ($delApp) {
        $delDb = promptYesNo('Drop database?', false);

        if ($delDb && file_exists($envPath)) {
            dropDatabaseFromEnv($root, $envPath);
        }

        deleteAppFiles($root, $appPaths);
    } else {
        echo "Keeping existing application. Publishing devlink config and running composer update.\n";
        $keepExistingApp = true;
    }
}

if (! $keepExistingApp) {
    echo "🚀 Building moox dev app with Laravel {$laravelVersion}\n";

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
} else {
    $database = databaseDriverFromEnvFile($root);
}

publishDevlinkConfig($root);

offerDevlinkConfigPause($root);

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
    $sqlitePath = $root.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';
    if (! is_dir(dirname($sqlitePath))) {
        mkdir(dirname($sqlitePath), 0755, true);
    }
    if (! file_exists($sqlitePath)) {
        touch($sqlitePath);
    }
}

echo "✅ moox is prepared, use `php artisan moox:install` to install...\n";
