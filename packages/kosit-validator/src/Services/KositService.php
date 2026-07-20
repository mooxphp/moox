<?php

declare(strict_types=1);

namespace Moox\KositValidator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Moox\KositValidator\DTOs\KositResult;
use Moox\KositValidator\Support\KositOutputPath;
use Moox\KositValidator\Support\KositValidatorArtifact;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class KositService
{
    public function jarPath(): string
    {
        $dir = config('kosit-validator.base_path').'/'.config('kosit-validator.paths.validator_dir');
        $expectedName = KositValidatorArtifact::expectedJarFilename();
        $path = $dir.'/'.$expectedName;

        if (is_file($path)) {
            return $path;
        }

        throw new RuntimeException("Expected validator JAR {$expectedName} not found in {$dir}. Run php artisan kosit:install first.");
    }

    public function scenariosPath(): string
    {
        $dir = config('kosit-validator.base_path').'/'.config('kosit-validator.paths.xrechnung_dir');

        if (! is_dir($dir)) {
            throw new RuntimeException("No scenarios.xml found in {$dir}. Run php artisan kosit:install first.");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }
            if ($file->getFilename() === 'scenarios.xml') {
                return $file->getPathname();
            }
        }

        throw new RuntimeException("No scenarios.xml found in {$dir}. Run php artisan kosit:install first.");
    }

    public function repositoryPath(): string
    {
        return dirname($this->scenariosPath());
    }

    /**
     * @param  string|null  $reportDirectory  Absolute filesystem directory for validator output (-o).
     *                                        When null, uses `kosit-validator.output.path` config.
     */
    public function validate(string $xmlPath, ?string $reportDirectory = null): KositResult
    {
        if (! file_exists($xmlPath)) {
            throw new RuntimeException("File not found: {$xmlPath}");
        }

        $resolvedXmlPath = realpath($xmlPath);
        $inputPath = $resolvedXmlPath !== false ? $resolvedXmlPath : $xmlPath;

        $reportDir = $reportDirectory !== null
            ? rtrim($reportDirectory, '/\\')
            : KositOutputPath::resolve();

        File::ensureDirectoryExists($reportDir);

        $java = config('kosit-validator.java_binary', 'java');

        $result = Process::run([
            $java,
            '-jar', $this->jarPath(),
            '-s', $this->scenariosPath(),
            '-r', $this->repositoryPath(),
            '-o', $reportDir,
            '-h',
            $inputPath,
        ]);

        $baseName = pathinfo($xmlPath, PATHINFO_FILENAME);
        $reportXml = $reportDir.'/'.$baseName.'-report.xml';
        $reportHtml = $reportDir.'/'.$baseName.'-report.html';

        return new KositResult(
            exitCode: $result->exitCode(),
            stdout: $result->output(),
            stderr: $result->errorOutput(),
            reportXmlPath: file_exists($reportXml) ? $reportXml : null,
            reportHtmlPath: file_exists($reportHtml) ? $reportHtml : null,
            xmlPath: $inputPath,
        );
    }

    public function isInstalled(): bool
    {
        try {
            $this->jarPath();
            $this->scenariosPath();

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function javaAvailable(): bool
    {
        $java = config('kosit-validator.java_binary', 'java');
        $result = Process::run([$java, '-version']);

        return $result->successful();
    }
}
