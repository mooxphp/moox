<?php

// Parse parameters
$params = getopt('l:d');
$laravelVersion = $params['l'] ?? null;
$delete = isset($params['d']);

// Delete app folders
if ($delete) {
    exec('rm -rf app bootstrap config database public resources routes storage tests vendor composer.json composer.lock .env artisan package.json phpunit.xml vite.config.js');
    echo "✅ App folders deleted.\n";
    exit;
}

// Enforce dev mode explicitly
putenv('APP_ENV=development');
putenv('COMPOSER_NO_DEV=0');

// Create Laravel app (without install)
exec('composer create-project laravel/laravel laravel-temp --no-install');

// Move Laravel app without overwriting existing files
exec('cp -rn laravel-temp/* . 2>/dev/null || true');

// Clean up temp directory
exec('rm -rf laravel-temp');

// Read .env manually if needed
if (! function_exists('env')) {
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if ($value === null && file_exists(__DIR__.'/.env')) {
            foreach (file(__DIR__.'/.env') as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
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

// Load devlink config
$config = require __DIR__.'/packages/devlink/config/devlink.php';

// Build composer.json
$composer = [
    'name' => 'moox/dev-app',
    'type' => 'project',
    'require' => [
        'laravel/laravel' => $laravelVersion ? '^'.$laravelVersion : '^12.0',
    ],
    'autoload' => [
        'psr-4' => [
            'App\\' => 'app/',
        ],
    ],
    'repositories' => [],
    'minimum-stability' => 'dev',
    'prefer-stable' => true,
];

$composer['config'] = [
    'allow-plugins' => [
        'pestphp/pest-plugin' => true,
    ],
];

// Add local path packages
foreach ($config['packages'] as $name => $pkg) {
    if (! ($pkg['active'] ?? false)) {
        continue;
    }

    if (! isset($pkg['type']) || ! in_array($pkg['type'], ['local', 'public'])) {
        continue;
    }

    $pkgPath = $pkg['path'] ?? null;
    $pkgPath = str_replace('../moox/', '', $pkgPath);
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

// Write composer.json
file_put_contents(
    __DIR__.'/composer.json',
    json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

// Create .env from .env.example
file_put_contents(
    __DIR__.'/.env',
    file_get_contents(__DIR__.'/.env.example')
);

// Run composer update (with error handling and output)
echo "▶️ Running composer update...\n";
exec('composer update --no-interaction --prefer-dist', $output, $exitCode);
echo implode("\n", $output);
if ($exitCode !== 0) {
    echo "❌ Composer update failed with code $exitCode\n";
    exit($exitCode);
}

// Sanity check
if (! file_exists(__DIR__.'/vendor/phpstan/phpstan/phpstan')) {
    echo "❌ PHPStan binary not found – likely not installed.\n";
    exit(1);
}

if (! file_exists(__DIR__.'/vendor/pestphp/pest/bin/pest')) {
    echo "❌ Pest binary not found – likely not installed.\n";
    exit(1);
}

exec('composer install --no-interaction --prefer-dist');
exec('composer dump-autoload -o');
exec('chmod +x vendor/bin/* || true');

echo "✅ Moox is ready.\n";
