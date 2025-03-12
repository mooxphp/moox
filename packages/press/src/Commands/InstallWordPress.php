<?php

namespace Moox\Press\Commands;

use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

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
    public function handle(): void
    {
        $this->art();
        $this->welcome();
        $this->checkDotenv();
        $this->getDotenv();
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
        ▓▒░░░░▒▓    ▓▓░░░░░▓▓    ▓▓░░░░▒▓░░░░▒▓▓        ▓▓▒░░░░░▒░░▒▓▓        ▓���░░░░▒▓▓▓▒░░░░░▒░░░░░▒▓
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

    public function checkDotenv(): void
    {
        if (! File::exists(base_path('.env'))) {
            alert('No .env file found, please install Laravel with Moox Press first.');
            exit(1);
        }

        info('.env file found, checking for required variables...');
    }

    public function getDotenv(): array
    {
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
            $value = config($variable);

            if ($value === null) {
                $missingVariables[] = $variable;
            } else {
                // Convert string 'true'/'false' to actual booleans
                if ($value === 'false') {
                    $value = false;
                } elseif ($value === 'true') {
                    $value = true;
                }

                $envVariables[$variable] = $value;
            }
        }

        if ($missingVariables !== []) {
            warning('The following required variables are missing from your .env file:');
            foreach ($missingVariables as $variable) {
                $this->line('- '.$variable);
            }

            warning('Please add the missing variables to your .env file and rerun this command.');
            exit(1);
        }

        info('All required variables are present in .env.');

        return $envVariables;
    }

    public function testDatabaseConnection(): void
    {
        info('Testing database connection...');

        try {
            // Attempt to connect to the database
            DB::connection()->getPdo();
            info('Database connection successful.');
        } catch (Exception $exception) {
            alert('Failed to connect to the database. Please check your database credentials in the .env file.');
            $this->line($exception->getMessage());
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
            if (strtolower((string) $overwrite) !== 'yes') {
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

        $process = new Process(['composer', 'install'], $publicDirectory);
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

        $wpPath = config('press.wordpress_path', '/public/wp');
        $fullWpPath = base_path(trim((string) $wpPath, '/'));

        if (! File::exists($fullWpPath)) {
            File::makeDirectory($fullWpPath, 0755, true);
            info(sprintf('WordPress directory created at %s.', $fullWpPath));
        } else {
            info(sprintf('WordPress directory already exists at %s.', $fullWpPath));
        }

        $wpConfigSource = __DIR__.'/../../wordpress/wp-config.php';
        $wpConfigDestination = $fullWpPath.'/wp-config.php';

        if (File::exists($wpConfigDestination)) {
            $overwrite = $this->ask('The wp-config.php file already exists in the WordPress directory. Do you want to overwrite it? (yes/no)', 'no');
            if (strtolower((string) $overwrite) !== 'yes') {
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

        $process = new Process(['wp', '--version']);
        $process->run();

        if ($process->isSuccessful()) {
            $this->info('wp-cli is already installed.');

            return;
        }

        $this->warn('wp-cli is not installed. Installing wp-cli...');

        $this->info('Downloading wp-cli.phar...');

        $downloadProcess = new Process([
            'curl',
            '-O',
            'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar',
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
            $chmodProcess = new Process([
                'chmod',
                '+x',
                base_path('wp-cli.phar'),
            ]);
            $chmodProcess->run();

            if (! $chmodProcess->isSuccessful()) {
                $this->error('Failed to make wp-cli.phar executable.');
                $this->line($chmodProcess->getErrorOutput());
                exit(1);
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $this->info('Moving wp-cli.phar to a user directory in your PATH...');

            // Determine a directory that is in the user's PATH and doesn't require admin rights
            $targetDir = getenv('APPDATA').'\Composer\vendor\bin';
            if (! file_exists($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir.'\wp.bat';

            // Create a .bat file that runs the .phar
            $batContent = "@ECHO OFF\r\nphp \"%~dp0wp-cli.phar\" %*";
            file_put_contents($targetPath, $batContent);

            if (! @rename(base_path('wp-cli.phar'), $targetDir.'\wp-cli.phar')) {
                $this->error('Failed to move wp-cli.phar to '.$targetDir);
                exit(1);
            } else {
                $this->info('wp-cli installed successfully in '.$targetDir);
            }
        } else {
            $this->info('Moving wp-cli.phar to /usr/local/bin/wp...');
            $moveProcess = new Process([
                'mv',
                base_path('wp-cli.phar'),
                '/usr/local/bin/wp',
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

        $env = $this->getDotenv();

        $wpPath = base_path(trim((string) $env['WP_PATH'], '/'));
        if (! File::exists($wpPath.'/wp-config.php')) {
            alert('wp-config.php not found! Please ensure the file is created and configured.');
            exit(1);
        }

        $siteUrl = $env['APP_URL'].$env['WP_SLUG'];
        $defaultSiteTitle = $env['APP_NAME'];
        $siteTitle = $this->ask('Please enter the site title', $defaultSiteTitle);
        $adminUser = 'sysadm';
        $adminPassword = $this->generateSecurePassword();
        $adminEmail = $this->ask('Please enter the admin email');

        info('A secure password has been generated: '.$adminPassword);
        warning('Please make sure to save this password as it will not be shown again.');

        $command = [
            'wp',
            'core',
            'install',
            '--url='.$siteUrl,
            '--title='.$siteTitle,
            '--admin_user='.$adminUser,
            '--admin_password='.$adminPassword,
            '--admin_email='.$adminEmail,
        ];

        foreach ($env as $key => $value) {
            if (is_bool($value)) {
                $env[$key] = $value ? 'true' : 'false';
            }
        }

        $process = new Process($command, $wpPath, $env);
        $process->setTimeout(null);

        $process->run();

        if ($process->isSuccessful()) {
            info('WordPress installation completed successfully.');
        } else {
            alert('WordPress installation failed.');
            $this->line($process->getErrorOutput());
            exit(1);
        }

        $this->installAndActivateDefaultTheme($wpPath);
    }

    protected function installAndActivateDefaultTheme(string $fullWpPath): void
    {
        $this->info('Ensuring a default theme is installed and activated...');

        $checkThemeProcess = new Process([
            'wp',
            'theme',
            'is-installed',
            'twentytwentyfour',
        ], $fullWpPath);
        $checkThemeProcess->run();

        if (! $checkThemeProcess->isSuccessful()) {
            $this->info('Default theme twentytwentyfour is not installed. Installing it now...');

            $installThemeProcess = new Process([
                'wp',
                'theme',
                'install',
                'twentytwentyfour',
                '--activate',
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

        $wpPath = config('press.wordpress_path', '/public/wp');
        $fullWpPath = base_path(trim((string) $wpPath, '/'));

        $pluginsPath = $fullWpPath.'/wp-content/plugins';

        $pluginSource = __DIR__.'/../../wordpress/plugins/moox-press';
        $pluginDestination = $pluginsPath.'/moox-press';

        if (! File::exists($pluginSource)) {
            alert('The Moox Press plugin source directory does not exist.');
            exit(1);
        }

        if (File::exists($pluginDestination)) {
            $overwrite = $this->ask('The Moox Press plugin already exists in the plugins directory. Do you want to overwrite it? (yes/no)', 'no');
            if (strtolower((string) $overwrite) !== 'yes') {
                info('Moox Press plugin was not overwritten.');

                return;
            }

            File::deleteDirectory($pluginDestination);
        }

        info('Copying the Moox Press plugin to the WordPress plugins directory...');
        File::copyDirectory($pluginSource, $pluginDestination);

        info('Activating the Moox Press plugin...');
        $activateCommand = ['wp', 'plugin', 'activate', 'moox-press'];
        $process = new Process($activateCommand, $fullWpPath);
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
     * Generate a secure password with 20 characters, must include
     * uppercase, lowercase, number, and special character.
     */
    protected function generateSecurePassword(): string
    {
        $length = 20;
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $special = '!@#$%^&*()';
        $all = $lowercase.$uppercase.$numbers.$special;

        $password = '';
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 0; $i < $length - 4; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
