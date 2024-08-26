<?php

namespace Moox\Press\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class InstallWordPress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxpress:wpinstall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs WordPress with PHPdotenv for Moox Press.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->art();
        $this->welcome();
        $this->testDatabaseConnection();
        $this->prepareComposer();
        $this->composerInstall();
        $this->prepareWpConfig();
        $this->useOrInstallWpCli();
        $this->wpInstall();
        $this->pressPluginInstall();
        $this->sayGoodbye();
    }

    public function art(): void
    {
        info('

        ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ ▓▓▓▓▓▓▓▓▓▓▓       ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓   ▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓
        ▓▓▒░░▒▓▓▒▒░░░░░░▒▒▓▓▓▒░░░░░░░▒▓▓   ▓▓▓▓▒░░░░░░░▒▓▓▓▓     ▓▓▓▓▓▒░░░░░░░▒▒▓▓▓▓▓▒▒▒▒▓▓      ▓▓▓▒▒▒▒▓▓
        ▓▒░░░░░░░░░░░░░░░░░░░░░░░░░░░░░▓▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓░░░░░▒▓▓   ▓▓▒░░░░░▓▓
        ▓▒░░░░░░▒▓▓▓▓▒░░░░░░░▒▓▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓▓▓▒░░░░░░░▓▓▓▓░░░░░░▒▓▓▓▓▓░░░░░░▒▓▓░░░░░▒▓▓▓▓▓░░░░░▒▓▓
        ▓▒░░░░▓▓▓▓  ▓▓░░░░░▓▓▓  ▓▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▓░░░░░▓░░░░░░▓▓▓▓   ▓▓▓▒░░░░▓▓▓▒░░░░░▓▓▓░░░░░▓▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▓░░▒░░░░░▓▓▓        ▓▓░░░░▒▓▓▓▓░░░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓          ▓▓▓░░░░░▒▓▓          ▓▓▒░░░░▓ ▓▓▓░░░░░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▒░░░░░▒░░▒▓▓        ▓▓░░░░▒▓▓▓▒░░░░░▒░░░░░▒▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓░░░░▒▓▓▓   ▓▓▓▒░░░░░▒▒░░░░░▒▓▓▓   ▓▓▓░░░░░▓▓▓░░░░░▒▓▓▓░░░░░▒▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓░░░░░░▒▒▓▓▒░░░░░░▒▓▓▓▓░░░░░░░▒▒▓▓▒░░░░░░▓▓▓░░░░░▒▓▓▓▓▓▒░░░░░▓▓
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▓ ▓▓▓▓▒░░░░░░░░░░░░░▒▓▓▒░░░░░▓▓▓   ▓▓▒░░░░░▒▓
        ▓▓░░░▒▓▓    ▓▓▒░░░▒▓▓    ▓▓░░░░▓▓  ▓▓▓▓▒░░░░░░▒▒▓▓▓▓     ▓▓▓▓▓▒▒░░░░░▒▒▓▓▓▓▓░░░░▒▓▓      ▓▓▓░░░░▒▓
        ▓▓▓▓▓▓▓      ▓▓▓▓▓▓▓     ▓▓▓▓▓▓▓▓    ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓▓

        ');
    }

    public function welcome(): void
    {
        info('Welcome to Moox Press WordPress Installer');
    }

    public function getDotenv(): array
    {
        if (! File::exists(base_path('.env'))) {
            alert('No .env file found, please install Laravel with Moox Press first.');
            exit(1);
        }

        info('.env file found, checking for required variables...');

        $requiredVariables = [
            'APP_NAME',
            'APP_URL',
            'WP_PREFIX',
            'WP_PATH',
            'WP_SLUG',
            'ADMIN_SLUG',
            'WP_DEBUG',
            'WP_DEBUG_LOG',
            'WP_DEBUG_DISPLAY',
            'WP_MEMORY_LIMIT',
            'LOCK_WP',
            'AUTH_WP',
            'REDIRECT_INDEX',
            'REDIRECT_TO_WP',
            'REDIRECT_LOGIN',
            'REDIRECT_LOGOUT',
            'REDIRECT_EDITOR',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
        ];

        $missingVariables = [];
        $envVariables = [];

        foreach ($requiredVariables as $variable) {
            if (env($variable) === null) {
                $missingVariables[] = $variable;
            }
        }

        if (! empty($missingVariables)) {
            warning('The following required variables are missing from your .env file:');
            foreach ($missingVariables as $variable) {
                $this->line("- $variable");
            }

            warning('Please add the missing variables to your .env file and rerun this command.');
            exit(1);
        }

        info('All required variables are present in .env.');

        foreach ($requiredVariables as $variable) {
            $value = env($variable);
            if ($value === null) {
                throw new \RuntimeException("Environment variable $variable is not set. Please check your .env file.");
            }
            $envVariables[$variable] = $value;
        }

        return $envVariables;
    }

    public function testDatabaseConnection(): void
    {
        info('Testing database connection...');

        try {
            // Attempt to connect to the database
            \DB::connection()->getPdo();
            info('Database connection successful.');
        } catch (\Exception $e) {
            alert('Failed to connect to the database. Please check your database credentials in the .env file.');
            $this->line($e->getMessage());
            exit(1);
        }
    }

    public function prepareComposer(): void
    {
        info('Preparing composer.json file...');

        $composerSource = __DIR__.'/../../wordpress/composer.json';
        $composerDestination = public_path('composer.json');

        if (File::exists($composerDestination)) {
            $overwrite = $this->ask('The composer.json file already exists in /public. Do you want to overwrite it? (yes/no)', 'no');
            if (strtolower($overwrite) !== 'yes') {
                info('composer.json file was not overwritten.');
            } else {
                File::copy($composerSource, $composerDestination);
                info('composer.json file has been overwritten.');
            }
        } else {
            File::copy($composerSource, $composerDestination);
            info('composer.json copied to /public directory.');
        }
    }

    public function composerInstall(): void
    {
        info('Running Composer install...');

        $publicDirectory = public_path();

        $process = new \Symfony\Component\Process\Process(['composer', 'install'], $publicDirectory);
        $process->setTimeout(null);

        $process->run();

        if ($process->isSuccessful()) {
            info('Composer install completed successfully.');
        } else {
            alert('Composer install failed.');
            $this->line($process->getErrorOutput());
            exit(1);
        }
    }

    public function prepareWpConfig(): void
    {
        info('Preparing wp-config.php file...');

        $wpPath = env('WP_PATH', '/public/wp');
        $fullWpPath = base_path(trim($wpPath, '/'));

        if (! File::exists($fullWpPath)) {
            File::makeDirectory($fullWpPath, 0755, true);
            info("WordPress directory created at {$fullWpPath}.");
        } else {
            info("WordPress directory already exists at {$fullWpPath}.");
        }

        $wpConfigSource = __DIR__.'/../../wordpress/wp-config.php';
        $wpConfigDestination = $fullWpPath.'/wp-config.php';

        if (File::exists($wpConfigDestination)) {
            $overwrite = $this->ask('The wp-config.php file already exists in the WordPress directory. Do you want to overwrite it? (yes/no)', 'no');
            if (strtolower($overwrite) !== 'yes') {
                info('wp-config.php file was not overwritten.');
            } else {
                File::copy($wpConfigSource, $wpConfigDestination);
                info('wp-config.php file has been overwritten.');
            }
        } else {
            File::copy($wpConfigSource, $wpConfigDestination);
            info('wp-config.php copied to the WordPress directory.');
        }
    }

    public function useOrInstallWpCli(): void
    {
        $this->info('Checking for wp-cli...');

        $process = new \Symfony\Component\Process\Process(['wp', '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('wp-cli is already installed.');

            return;
        }

        $this->warn('wp-cli is not installed. Installing wp-cli...');

        $this->info('Downloading wp-cli.phar...');

        $downloadProcess = new \Symfony\Component\Process\Process([
            'curl', '-O', 'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar',
        ], base_path());
        $downloadProcess->setTimeout(null);
        $downloadProcess->run();

        if (! $downloadProcess->isSuccessful()) {
            $this->error('Failed to download wp-cli.phar.');
            $this->line($downloadProcess->getOutput());
            $this->line($downloadProcess->getErrorOutput());
            exit(1);
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $this->info('Making wp-cli.phar executable...');
            $chmodProcess = new \Symfony\Component\Process\Process([
                'chmod', '+x', base_path('wp-cli.phar'),
            ]);
            $chmodProcess->run();

            if (! $chmodProcess->isSuccessful()) {
                $this->error('Failed to make wp-cli.phar executable.');
                $this->line($chmodProcess->getErrorOutput());
                exit(1);
            }
        }

        // TODO: Test this on MacOS, Linux and Windows
        if (PHP_OS_FAMILY === 'Windows') {
            $this->info('Moving wp-cli.phar to a directory in your PATH...');
            if (! @rename(base_path('wp-cli.phar'), 'C:\Windows\System32\wp.bat')) {
                $this->error('Failed to move wp-cli.phar to C:\Windows\System32\wp.bat.');
                exit(1);
            }
        } else {
            $this->info('Moving wp-cli.phar to /usr/local/bin/wp...');
            $moveProcess = new \Symfony\Component\Process\Process([
                'sudo', '-E', 'mv', base_path('wp-cli.phar'), '/usr/local/bin/wp',
            ]);

            $moveProcess->run();

            if ($moveProcess->isSuccessful()) {
                $this->info('wp-cli installed successfully.');
            } else {
                $this->error('Failed to move wp-cli.phar to /usr/local/bin/wp.');
                $this->line($moveProcess->getErrorOutput());
                exit(1);
            }
        }
    }

    public function wpInstall(): void
    {
        info('Installing WordPress...');

        $wpPath = env('WP_PATH', '/public/wp');
        $fullWpPath = base_path(trim($wpPath, '/'));

        // Ensure wp-config.php exists and has correct settings
        if (! File::exists($fullWpPath.'/wp-config.php')) {
            alert('wp-config.php not found! Please ensure the file is created and configured.');
            exit(1);
        }

        // Test the environment variables
        $env = $this->getDotenv();
        foreach ($env as $key => $value) {
            if (! $value) {
                alert("Environment variable $key is not set. Please check your .env file.");
                exit(1);
            }
        }

        $siteUrl = $env['APP_URL'].$env['WP_SLUG'];
        $defaultSiteTitle = $env['APP_NAME'];
        $siteTitle = $this->ask('Please enter the site title', $defaultSiteTitle);
        $adminUser = 'sysadm';
        $adminPassword = $this->generateSecurePassword();
        $adminEmail = $this->ask('Please enter the admin email');

        info("A secure password has been generated: $adminPassword");
        warning('Please make sure to save this password as it won’t be shown again.');

        $command = [
            'wp', 'core', 'install',
            '--url='.$siteUrl,
            '--title='.$siteTitle,
            '--admin_user='.$adminUser,
            '--admin_password='.$adminPassword,
            '--admin_email='.$adminEmail,
        ];

        $process = new \Symfony\Component\Process\Process($command, $fullWpPath, $env);
        $process->setTimeout(null);

        $process->run();

        if ($process->isSuccessful()) {
            info('WordPress installation completed successfully.');
        } else {
            alert('WordPress installation failed.');
            $this->line($process->getErrorOutput());
            exit(1);
        }

        $this->installAndActivateDefaultTheme($fullWpPath);
    }

    protected function installAndActivateDefaultTheme(string $fullWpPath): void
    {
        $this->info('Ensuring a default theme is installed and activated...');

        $checkThemeProcess = new \Symfony\Component\Process\Process([
            'wp', 'theme', 'is-installed', 'twentytwentyfour',
        ], $fullWpPath);
        $checkThemeProcess->run();

        if (! $checkThemeProcess->isSuccessful()) {
            $this->info('Default theme twentytwentyfour is not installed. Installing it now...');

            $installThemeProcess = new \Symfony\Component\Process\Process([
                'sudo', '-u', $user, 'wp', 'theme', 'install', 'twentytwentyfour', '--activate',
            ], $fullWpPath);
            $installThemeProcess->setTimeout(null);
            $installThemeProcess->run();

            if ($installThemeProcess->isSuccessful()) {
                $this->info('Default theme twentytwentyfour installed and activated successfully.');
            } else {
                $this->error('Failed to install or activate the default theme.');
                $this->line($installThemeProcess->getErrorOutput());
                exit(1);
            }
        } else {
            $this->info('Default theme twentytwentyfour is already installed.');
        }
    }

    public function pressPluginInstall(): void
    {
        info('Installing the Moox Press plugin...');

        $wpPath = env('WP_PATH', '/public/wp');
        $fullWpPath = base_path(trim($wpPath, '/'));

        $pluginsPath = $fullWpPath.'/wp-content/plugins';

        $pluginSource = __DIR__.'/../../wordpress/plugins/moox-press';
        $pluginDestination = $pluginsPath.'/moox-press';

        if (! File::exists($pluginSource)) {
            alert('The Moox Press plugin source directory does not exist.');
            exit(1);
        }

        if (File::exists($pluginDestination)) {
            $overwrite = $this->ask('The Moox Press plugin already exists in the plugins directory. Do you want to overwrite it? (yes/no)', 'no');
            if (strtolower($overwrite) !== 'yes') {
                info('Moox Press plugin was not overwritten.');

                return;
            }

            File::deleteDirectory($pluginDestination);
        }

        info('Copying the Moox Press plugin to the WordPress plugins directory...');
        File::copyDirectory($pluginSource, $pluginDestination);

        info('Activating the Moox Press plugin...');
        $activateCommand = ['wp', 'plugin', 'activate', 'moox-press'];
        $process = new \Symfony\Component\Process\Process($activateCommand, $fullWpPath);
        $process->setTimeout(null);
        $process->run();

        if ($process->isSuccessful()) {
            info('Moox Press plugin installed and activated successfully.');
        } else {
            alert('Failed to activate the Moox Press plugin.');
            $this->line($process->getErrorOutput());
            exit(1);
        }
    }

    public function sayGoodbye(): void
    {
        note('Moox Press WordPress installed successfully. Enjoy!');
    }

    /**
     * Generate a secure password with 20 characters, including
     * uppercase, lowercase, numbers, and special characters.
     */
    protected function generateSecurePassword(): string
    {
        $length = 20;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
