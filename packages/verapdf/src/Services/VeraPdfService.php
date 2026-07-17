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

        $result = Process::timeout(600)->run([
            $launcher,
            '-f', $flavour,
            '--format', 'xml',
            $inputPath,
        ]);

        $baseName = pathinfo($pdfPath, PATHINFO_FILENAME);
        $reportXml = $reportDir.'/'.$baseName.'-report.xml';
        $reportHtml = $reportDir.'/'.$baseName.'-report.html';

        $stdout = $result->output();
        if ($stdout !== '') {
            File::put($reportXml, $stdout);
        }

        $htmlResult = Process::timeout(600)->run([
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

    public function javaAvailable(): bool
    {
        $java = config('verapdf.java_binary', 'java');
        $result = Process::run([$java, '-version']);

        return $result->successful();
    }
}
