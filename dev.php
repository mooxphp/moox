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
  --delete            Delete generated Laravel app files from repo root (asks to drop DB)
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

        echo "Bitte y oder n eingeben.\n";
    }
}

function requireInteractiveForPrompts(): void
{
    if (! stream_isatty(STDIN)) {
        echo "❌ Interaktive Eingabe nicht möglich (kein TTY).\n";
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
            echo "❌ DB_DATABASE fehlt in .env.\n";
            exit(1);
        }

        $full = str_starts_with($dbFile, DIRECTORY_SEPARATOR) ? $dbFile : $root.DIRECTORY_SEPARATOR.$dbFile;

        if (file_exists($full) || is_link($full)) {
            unlink($full);
            echo "✅ SQLite-Datei gelöscht: {$full}\n";
        } else {
            echo "ℹ️ SQLite-Datei nicht gefunden: {$full}\n";
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
            echo "❌ DB_DATABASE fehlt in .env.\n";
            exit(1);
        }

        if (extension_loaded('pdo_mysql')) {
            $dsn = 'mysql:host='.$host.';port='.$port;
            try {
                $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $quoted = str_replace('`', '``', $database);
                $pdo->exec('DROP DATABASE IF EXISTS `'.$quoted.'`');
                echo "✅ MySQL-Datenbank gelöscht: {$database}\n";
            } catch (PDOException $e) {
                echo '❌ MySQL DROP DATABASE fehlgeschlagen: '.$e->getMessage()."\n";
                exit(1);
            }

            return;
        }

        $cmd = 'mysql -h'.escapeshellarg($host).' -P'.escapeshellarg((string) $port)
            .' -u'.escapeshellarg($user);

        if ($password !== '') {
            $cmd .= ' -p'.escapeshellarg($password);
        }

        $quotedDb = str_replace('`', '``', $database);
        $cmd .= ' -e '.escapeshellarg('DROP DATABASE IF EXISTS `'.$quotedDb.'`');
        run($cmd);
        echo "✅ MySQL-Datenbank gelöscht: {$database}\n";

        return;
    }

    if (in_array($connection, ['pgsql', 'postgres', 'postgresql'], true)) {
        $host = (string) ($vars['DB_HOST'] ?? '127.0.0.1');
        $port = (int) ($vars['DB_PORT'] ?? 5432);
        $database = (string) ($vars['DB_DATABASE'] ?? '');
        $user = (string) ($vars['DB_USERNAME'] ?? 'postgres');
        $password = (string) ($vars['DB_PASSWORD'] ?? '');

        if ($database === '') {
            echo "❌ DB_DATABASE fehlt in .env.\n";
            exit(1);
        }

        if (extension_loaded('pdo_pgsql')) {
            $dsn = 'pgsql:host='.$host.';port='.$port.';dbname=postgres';
            try {
                $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $safe = str_replace('"', '""', $database);
                $pdo->exec('DROP DATABASE IF EXISTS "'.$safe.'" WITH (FORCE)');
                echo "✅ PostgreSQL-Datenbank gelöscht: {$database}\n";
            } catch (PDOException $e) {
                echo '❌ PostgreSQL DROP DATABASE fehlgeschlagen: '.$e->getMessage()."\n";
                exit(1);
            }

            return;
        }

        $cmd = 'PGHOST='.escapeshellarg($host)
            .' PGPORT='.escapeshellarg((string) $port)
            .' PGUSER='.escapeshellarg($user)
            .' PGPASSWORD='.escapeshellarg($password)
            .' dropdb --if-exists '
            .escapeshellarg($database);
        run($cmd);
        echo "✅ PostgreSQL-Datenbank gelöscht: {$database}\n";

        return;
    }

    echo "❌ Unbekannte DB_CONNECTION in .env: {$connection}\n";
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

$envPath = $root.DIRECTORY_SEPARATOR.'.env';

if ($delete) {
    $dropDb = false;

    if (file_exists($envPath)) {
        if (stream_isatty(STDIN)) {
            $dropDb = promptYesNo('Datenbank löschen?', true);
        } else {
            echo "ℹ️ Non-interaktiv: Datenbank wird nicht gelöscht (kein TTY).\n";
        }

        if ($dropDb) {
            dropDatabaseFromEnv($root, $envPath);
        }
    }

    deleteAppFiles($root, $appPaths);

    echo "✅ Generated Laravel app files deleted.\n";
    exit(0);
}

if (appLooksPresent($root)) {
    requireInteractiveForPrompts();

    $delApp = promptYesNo('Es liegt bereits eine App im Repo. App-Dateien löschen?', false);
    $delDb = promptYesNo('Datenbank löschen?', false);

    if (! $delApp) {
        echo "Abgebrochen.\n";
        exit(0);
    }

    if ($delDb && file_exists($envPath)) {
        dropDatabaseFromEnv($root, $envPath);
    }

    deleteAppFiles($root, $appPaths);
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
