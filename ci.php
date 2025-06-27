<?php

// not sure if we need this ... just use env-example
if (! function_exists('env')) {
    function env($key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if ($value === null && file_exists(__DIR__.'/.env')) {
            foreach (file(__DIR__.'/.env') as $line) {
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $_ENV[$k] = $_SERVER[$k] = $v;
            }
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }

        return $value ?? $default;
    }
}

// Load config
$config = require __DIR__.'/packages/devlink/config/devlink.php';

$composer = [
    'name' => 'moox/dev-app',
    'type' => 'project',
    'require' => [
        'laravel/framework' => '^11.0',
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

// Add local path packages
foreach ($config['packages'] as $name => $pkg) {
    if (! ($pkg['active'] ?? false)) {
        continue;
    }
    if (! isset($pkg['type']) || ! in_array($pkg['type'], ['local', 'public'])) {
        continue;
    }
    $pkgPath = $pkg['path'] ?? null;
    if (! $pkgPath || ! is_dir($pkgPath)) {
        continue;
    }

    $composer['require']["moox/{$name}"] = '*';
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

// Optional: create stub files if they don't exist
if (! is_file(__DIR__.'/routes/web.php')) {
    @mkdir(__DIR__.'/routes', 0777, true);
    file_put_contents(__DIR__.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/', fn() => 'It works!');\n");
}

if (! is_file(__DIR__.'/.env')) {
    file_put_contents(__DIR__.'/.env', "APP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\n");
}

echo "✅ composer.json generated. You can now run composer update.\n";
