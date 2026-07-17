<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\DTOs\VeraPdfResult;
use Moox\VeraPdf\Support\VeraPdfOutputPath;
use RuntimeException;

class VeraPdfService
{
    public function launcherPath(): string
    {
        $basePath = rtrim((string) config('verapdf.base_path'), '/\\');
        $launcher = (string) config('verapdf.paths.launcher', 'verapdf');
        $path = $basePath.'/'.$launcher;

        if (PHP_OS_FAMILY === 'Windows') {
            $bat = $path.'.bat';
            if (is_file($bat)) {
                return $bat;
            }
        }

        if (! is_file($path)) {
            throw new RuntimeException(
                "veraPDF launcher not found at {$path}. Run php artisan verapdf:install first."
            );
        }

        return $path;
    }

    /**
     * @param  string|null  $reportDirectory  Absolute filesystem directory for report output.
     *                                        When null, uses `verapdf.output.path` config.
     */
    public function validate(string $pdfPath, ?string $reportDirectory = null): VeraPdfResult
    {
        if (! $this->javaAvailable()) {
            throw new RuntimeException(
                'Java not found. Install a JRE/JDK on the server first (e.g. sudo apt install default-jre-headless).'
            );
        }

        if (! $this->isInstalled()) {
            throw new RuntimeException(
                'veraPDF is not installed. Run php artisan verapdf:install first.'
            );
        }

        if (! file_exists($pdfPath)) {
            throw new RuntimeException("File not found: {$pdfPath}");
        }

        $resolvedPdfPath = realpath($pdfPath);
        $inputPath = $resolvedPdfPath !== false ? $resolvedPdfPath : $pdfPath;

        $reportDir = $reportDirectory !== null
            ? rtrim($reportDirectory, '/\\')
            : VeraPdfOutputPath::resolve();

        File::ensureDirectoryExists($reportDir);

        $flavour = (string) config('verapdf.flavour', '3b');
        $launcher = $this->launcherPath();
        $processEnv = $this->processEnvironment();

        $result = Process::timeout(600)
            ->env($processEnv)
            ->run([
                $launcher,
                '-f', $flavour,
                '--format', 'xml',
                $inputPath,
            ]);

        $baseName = $this->safeReportBasename($pdfPath);
        $reportXml = $reportDir.'/'.$baseName.'-report.xml';
        $reportHtml = $reportDir.'/'.$baseName.'-report.html';

        $stdout = $result->output();
        if ($stdout !== '') {
            File::put($reportXml, $stdout);
        }

        $htmlResult = Process::timeout(600)
            ->env($processEnv)
            ->run([
                $launcher,
                '-f', $flavour,
                '--format', 'html',
                $inputPath,
            ]);

        if ($htmlResult->output() !== '') {
            File::put($reportHtml, $htmlResult->output());
        }

        return new VeraPdfResult(
            exitCode: $result->exitCode() ?? 1,
            stdout: $stdout,
            stderr: $result->errorOutput(),
            reportXmlPath: file_exists($reportXml) ? $reportXml : null,
            reportHtmlPath: file_exists($reportHtml) ? $reportHtml : null,
            pdfPath: $inputPath,
        );
    }

    public function isInstalled(): bool
    {
        try {
            $path = $this->launcherPath();

            return is_file($path) && (PHP_OS_FAMILY === 'Windows' || is_executable($path));
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * True when the CLI pack layout is present (launcher + bin/cli-*.jar).
     */
    public function hasCliBinaries(): bool
    {
        if (! $this->isInstalled()) {
            return false;
        }

        return $this->findCliJar() !== null;
    }

    /**
     * True when GUI pack artefacts are present (optional warning for slim installs).
     */
    public function hasGuiArtefacts(): bool
    {
        $basePath = rtrim((string) config('verapdf.base_path'), '/\\');

        if (is_file($basePath.'/verapdf-gui') || is_file($basePath.'/verapdf-gui.bat')) {
            return true;
        }

        return $this->findJarInBin('gui') !== null;
    }

    public function javaAvailable(): bool
    {
        $java = config('verapdf.java_binary', 'java');
        $result = Process::run([$java, '-version']);

        return $result->successful();
    }

    public function findCliJar(): ?string
    {
        return $this->findJarInBin('cli');
    }

    private function findJarInBin(string $needle): ?string
    {
        $binDir = rtrim((string) config('verapdf.base_path'), '/\\').'/bin';

        if (! is_dir($binDir)) {
            return null;
        }

        $matches = glob($binDir.'/*'.$needle.'*.jar') ?: [];

        return $matches[0] ?? null;
    }

    /**
     * Ensure a configured absolute Java binary is preferred by the veraPDF launcher script.
     *
     * @return array<string, string>
     */
    private function processEnvironment(): array
    {
        $java = (string) config('verapdf.java_binary', 'java');

        if ($java === '' || $java === 'java' || (! str_contains($java, '/') && ! str_contains($java, '\\'))) {
            return [];
        }

        $dir = dirname($java);
        $path = $dir.PATH_SEPARATOR.(getenv('PATH') ?: '');

        return ['PATH' => $path];
    }

    private function safeReportBasename(string $pdfPath): string
    {
        $baseName = basename(pathinfo($pdfPath, PATHINFO_FILENAME));
        $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName) ?? '';
        $baseName = trim($baseName, '._-');

        if ($baseName === '' || $baseName === '.' || $baseName === '..') {
            return 'verapdf-input';
        }

        return $baseName;
    }
}
