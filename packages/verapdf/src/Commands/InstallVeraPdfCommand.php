<?php

declare(strict_types=1);

namespace Moox\VeraPdf\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Moox\VeraPdf\Services\VeraPdfService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class InstallVeraPdfCommand extends Command
{
    protected $signature = 'verapdf:install
        {--force : Overwrite existing installation}';

    protected $description = 'Download and headless-install veraPDF into the configured base path';

    public function handle(VeraPdfService $veraPdf): int
    {
        $this->components->info('Checking Java ...');

        if (! $veraPdf->javaAvailable()) {
            $this->components->error(
                'Java not found. Install a JRE/JDK on the server first (e.g. sudo apt install default-jre-headless).'
            );

            return self::FAILURE;
        }

        $this->components->info('Java found.');

        $basePath = rtrim((string) config('verapdf.base_path'), '/\\');
        $tmpDir = $basePath.'/'.config('verapdf.paths.install_tmp', 'tmp');

        if ($this->option('force') && File::exists($basePath)) {
            $this->components->warn("Deleting existing installation at {$basePath}");
            File::deleteDirectory($basePath);
        }

        if ($veraPdf->isInstalled() && ! $this->option('force')) {
            if ($veraPdf->hasCliBinaries()) {
                $this->components->info('veraPDF is already installed. Use --force to reinstall.');

                return self::SUCCESS;
            }

            $this->components->error(
                'veraPDF launcher found but CLI pack is missing. Run php artisan verapdf:install --force to install the CLI pack only.'
            );

            return self::FAILURE;
        }

        File::ensureDirectoryExists($basePath);
        File::ensureDirectoryExists($tmpDir);

        try {
            $zipPath = $tmpDir.'/verapdf-installer.zip';
            $this->downloadFile(
                (string) config('verapdf.installer.download_url'),
                $zipPath,
                'veraPDF v'.config('verapdf.installer.version')
            );

            $extractDir = $tmpDir.'/extracted';
            File::ensureDirectoryExists($extractDir);
            $this->extractZip($zipPath, $extractDir, 'veraPDF installer');

            $installerJar = $this->findInstallerJar($extractDir);
            $autoInstallXml = $tmpDir.'/auto-install.xml';
            $this->writeAutoInstallXml($autoInstallXml, $basePath);

            $this->components->info('Running headless veraPDF installer ...');

            $java = (string) config('verapdf.java_binary', 'java');
            $result = Process::timeout(1200)->run([
                $java,
                '-Djava.awt.headless=true',
                '-jar', $installerJar,
                $autoInstallXml,
            ]);

            if (! $result->successful() && ! $veraPdf->isInstalled()) {
                throw new RuntimeException(
                    'veraPDF installer failed: '.trim($result->errorOutput() ?: $result->output())
                );
            }

            $launcher = $basePath.'/'.config('verapdf.paths.launcher', 'verapdf');
            if (is_file($launcher) && PHP_OS_FAMILY !== 'Windows') {
                chmod($launcher, 0755);
            }
        } catch (RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if (File::exists($tmpDir)) {
                File::deleteDirectory($tmpDir);
            }
        }

        try {
            $launcherPath = $veraPdf->launcherPath();
        } catch (RuntimeException $e) {
            $this->components->error('Installation incomplete: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $veraPdf->hasCliBinaries()) {
            $hint = $veraPdf->hasGuiArtefacts()
                ? 'GUI pack artefacts found but CLI jar missing — auto-install must select the veraPDF CLI pack (not GUI).'
                : 'CLI jar missing under bin/ — auto-install must select the veraPDF CLI pack.';
            $this->components->error('Installation incomplete: '.$hint);

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('veraPDF CLI installation successful.');
        $this->line("  Launcher: <info>{$launcherPath}</info>");
        $cliJar = $veraPdf->findCliJar();
        if ($cliJar !== null) {
            $this->line("  CLI jar: <info>{$cliJar}</info>");
        }
        $this->newLine();
        $this->line('Test with: <comment>php artisan verapdf:validate /path/to/file.pdf</comment>');

        return self::SUCCESS;
    }

    private function downloadFile(string $url, string $target, string $label): void
    {
        $this->components->info("Downloading {$label} ...");

        $response = Http::timeout(600)
            ->sink($target)
            ->withOptions([
                'allow_redirects' => true,
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Download failed for {$label}: HTTP {$response->status()} from {$url}");
        }

        if (! File::exists($target) || File::size($target) === 0) {
            throw new RuntimeException("Download incomplete for {$label}");
        }
    }

    private function extractZip(string $zipFile, string $targetDir, string $label): void
    {
        $this->components->info("Extracting {$label} ...");

        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException("Cannot open ZIP: {$zipFile}");
        }

        if (! $zip->extractTo($targetDir)) {
            $zip->close();
            throw new RuntimeException("Cannot extract ZIP: {$zipFile}");
        }

        $zip->close();
    }

    private function findInstallerJar(string $extractDir): string
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if (str_contains($name, 'izpack-installer') && str_ends_with($name, '.jar')) {
                return $file->getPathname();
            }
        }

        throw new RuntimeException("No veraPDF IzPack installer JAR found under {$extractDir}");
    }

    private function writeAutoInstallXml(string $target, string $installPath): void
    {
        $stub = dirname(__DIR__, 2).'/resources/install/auto-install.xml.stub';

        if (! is_file($stub)) {
            throw new RuntimeException("Missing auto-install stub at {$stub}");
        }

        $contents = str_replace('{{INSTALL_PATH}}', $installPath, (string) file_get_contents($stub));
        File::put($target, $contents);
    }
}
