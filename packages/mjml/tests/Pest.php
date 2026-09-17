<?php

declare(strict_types=1);

use Moox\Mjml\Tests\TestCase;
use Spatie\Mjml\Mjml as SpatieMjml;
use Symfony\Component\Process\ExecutableFinder;

pest()->extend(TestCase::class)->in(__DIR__);

if (! function_exists('mjmlSpatiePackagePath')) {
    function mjmlSpatiePackagePath(): string
    {
        $file = (new ReflectionClass(SpatieMjml::class))->getFileName();

        if (is_string($file)) {
            return dirname($file, 2);
        }

        if (function_exists('base_path')) {
            return base_path('vendor/spatie/mjml-php');
        }

        return '';
    }
}

if (! function_exists('nodeMjmlAvailable')) {
    function nodeMjmlAvailable(): bool
    {
        $extraDirectories = [
            '/usr/local/bin',
            '/opt/homebrew/bin',
        ];

        $nodePathFromEnv = getenv('MJML_NODE_PATH');

        if (is_string($nodePathFromEnv) && $nodePathFromEnv !== '') {
            array_unshift($extraDirectories, $nodePathFromEnv);
        }

        $node = (new ExecutableFinder)->find('node', extraDirs: $extraDirectories);

        if (! is_string($node) || $node === '') {
            return false;
        }

        $spatie = mjmlSpatiePackagePath();

        return is_file($spatie.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'mjml.mjs')
            && is_dir($spatie.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'mjml');
    }
}

if (! function_exists('skipUnlessNodeMjmlAvailable')) {
    function skipUnlessNodeMjmlAvailable(): void
    {
        if (nodeMjmlAvailable()) {
            return;
        }

        test()->markTestSkipped('Node MJML is not available (node binary, Spatie mjml.mjs, and node_modules/mjml).');
    }
}

if (! function_exists('skipUnlessMjmlEngineAvailable')) {
    function skipUnlessMjmlEngineAvailable(bool $usePhpRenderer): void
    {
        if ($usePhpRenderer) {
            return;
        }

        skipUnlessNodeMjmlAvailable();
    }
}
