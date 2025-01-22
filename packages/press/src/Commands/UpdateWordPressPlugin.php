<?php

namespace Moox\Press\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\alert;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class UpdateWordPressPlugin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mooxpress:updatewpplugin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the Moox Press WordPress plugin from insidethe Moox Press Laravel package.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->art();
        $this->welcome();
        $this->checkDotenv();
        $this->getDotenv();
        $this->pressPluginInstall();
        $this->updateWpConfig();
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
            'WP_PATH',
        ];

        $missingVariables = [];
        $envVariables = [];

        foreach ($requiredVariables as $variable) {
            $value = env($variable);

            if ($value === null) {
                $missingVariables[] = $variable;
            } else {
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
                $this->line('- ' . $variable);
            }

            warning('Please add the missing variables to your .env file and rerun this command.');
            exit(1);
        }

        info('All required variables are present in .env.');

        return $envVariables;
    }

    public function pressPluginInstall(): void
    {
        info('Updating the Moox Press plugin...');

        $wpPath = env('WP_PATH', '/public/wp');
        $fullWpPath = base_path(trim((string) $wpPath, '/'));

        $pluginsPath = $fullWpPath.'/wp-content/plugins';

        $pluginSource = __DIR__.'/../../wordpress/plugins/moox-press';
        $pluginDestination = $pluginsPath.'/moox-press';

        if (! File::exists($pluginSource)) {
            alert('The Moox Press plugin source directory does not exist. It seems that WordPress is not installed.');
            exit(1);
        }

        if (File::exists($pluginDestination)) {
            info('Deleting the existing Moox Press plugin...');
            File::deleteDirectory($pluginDestination);
        }

        info('Copying the Moox Press plugin to the WordPress plugins directory...');
        File::copyDirectory($pluginSource, $pluginDestination);
    }

    public function updateWpConfig(): void
    {
        info('Updating the wp-config.php file...');

        $wpPath = env('WP_PATH', '/public/wp');
        $fullWpPath = base_path(trim((string) $wpPath, '/'));

        $wpconfigPath = $fullWpPath.'/wp-config.php';

        $configSource = __DIR__.'/../../wordpress/wp-config.php';
        $configDestination = $wpconfigPath;

        if (! File::exists($configSource)) {
            alert('The source wp-config.php file does not exist.');
            exit(1);
        }

        if (File::exists($configDestination)) {
            info('Deleting the existing wp-config.php file...');
            File::delete($configDestination);
        }

        if (File::copy($configSource, $configDestination)) {
            info('wp-config.php file updated successfully.');
        } else {
            alert('Error occurred while copying the wp-config.php file.');
            exit(1);
        }
    }

    public function sayGoodbye(): void
    {
        note('Moox Press WordPress Plugin updated successfully. Enjoy!');
    }
}
