![Moox Jobs](https://github.com/mooxphp/moox/raw/main/art/banner/jobs.jpg)

# Moox Jobs

Managing Job Queues, Failed Jobs and Batches in Filament.

Alternative to Laravel Horizon, if you use the database driver for queues. Nice addon to Laravel Horizon, if you use Redis. See [Limitations](#limitations) below for more information about Redis, Amazon SQS and Beanstalkd compatibility. More information about Laravel Job Queues and how Moox Jobs works in our [Jobs for Beginners Guide](#jobs-for-beginners).

## Requirements

Moox Jobs requires

-   [PHP 8.1](https://www.php.net/) or higher
-   [Laravel 10](https://laravel.com/docs/installation) or higher
-   [Filament 3](https://filamentphp.com/docs/panels/installation) or higher

in short

```bash
composer create-project laravel/laravel moox-jobs-demo
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-user
```

## Upgrading from Moox Jobs V2

Moox Jobs V3 requires changes to the database schema. We made an convenient update command for you:

```bash
composer update
php artisan mooxjobs:update
```

The update command takes care about changing and creating the new fields without loosing data. Alternatively you may delete the job-manager table and simply run the following install command.

## Quick installation

These two commmands are all you need to install the package:

```bash
composer require moox/jobs
php artisan mooxjobs:install
```

Curious what the install command does? See [manual installation](#manual-installation) below.

## Features

### Jobs

Monitor your running and finished jobs.

![screenshot-jobs](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg)

### Jobs waiting

See all waiting Jobs queued, delete one, many or even all waiting jobs at once before they hit the queue. And, yes we do not only have dark mode.

![screenshot-waiting](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-waiting.jpg)

### Jobs failed

See all failed Jobs including details, retry or delete single jobs, many jobs or even all failed jobs at once.

![screenshot-details](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-details.jpg)

![screenshot-detail](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-detail.jpg)

### Job batches

Monitor your job batches, prune batches.

![screenshot-batches](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-batches.jpg)

## Manual Installation

This Laravel package is made for FilamentPHP and the awesome TALL-Stack. If you don't want to use our install command, follow thes manual steps to install the package.

Install the package via Composer:

```bash
composer require moox/jobs
```

Create the necessary tables:

```bash
php artisan vendor:publish --tag="jobs-manager-migration"
php artisan vendor:publish --tag="jobs-batch-migration"
php artisan vendor:publish --tag="jobs-queue-migration"
php artisan vendor:publish --tag="jobs-manager-foreigns-migration"

# Queue tables, if using the database driver
# Not required for Redis, Amazon SQS or Beanstalkd
php artisan queue:table
php artisan queue:failed-table
php artisan queue:batches-table

php artisan migrate
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="jobs-config"
```

This is the content of the published config file:

```php
return [
    'resources' => [
        'jobs' => [
            'enabled' => true,
            'label' => 'Job',
            'plural_label' => 'Jobs',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-play',
            'navigation_sort' => 1,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsResource::class,
        ],
        'jobs_waiting' => [
            'enabled' => true,
            'label' => 'Job waiting',
            'plural_label' => 'Jobs waiting',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-pause',
            'navigation_sort' => 2,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsWaitingResource::class,
        ],
        'failed_jobs' => [
            'enabled' => true,
            'label' => 'Failed Job',
            'plural_label' => 'Failed Jobs',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-exclamation-triangle',
            'navigation_sort' => 3,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobsFailedResource::class,
        ],
        'job_batches' => [
            'enabled' => true,
            'label' => 'Job Batch',
            'plural_label' => 'Job Batches',
            'navigation_group' => 'Job manager',
            'navigation_icon' => 'heroicon-o-inbox-stack',
            'navigation_sort' => 4,
            'navigation_count_badge' => true,
            'resource' => Moox\Jobs\Resources\JobBatchesResource::class,
        ],
    ],
    'pruning' => [
        'enabled' => true,
        'retention_days' => 7,
    ],
];
```

Register the Plugins in `app/Providers/Filament/AdminPanelProvider.php`:

```php
->plugins([
    \Moox\Jobs\JobsPlugin::make(),
    \Moox\Jobs\JobsWaitingPlugin::make(),
    \Moox\Jobs\JobsFailedPlugin::make(),
    \Moox\Jobs\JobsBatchesPlugin::make(),
])
```

Instead of publishing and modifying the config-file, you can also do all settings in AdminPanelProvider like so:

```php
->plugins([
	\Moox\Jobs\JobsPlugin::make()
	    ->label('Job runs')
	    ->pluralLabel('Jobs that seems to run')
	    ->enableNavigation(true)
	    ->navigationIcon('heroicon-o-face-smile')
	    ->navigationGroup('My Jobs and Queues')
	    ->navigationSort(5)
	    ->navigationCountBadge(true)
	    ->enablePruning(true)
	    ->pruningRetention(7),
	\Moox\Jobs\JobsWaitingPlugin::make()
	    ->label('Job waiting')
	    ->pluralLabel('Jobs waiting in line')
	    ->enableNavigation(true)
	    ->navigationIcon('heroicon-o-calendar')
	    ->navigationGroup('My Jobs and Queues')
	    ->navigationSort(5)
	    ->navigationCountBadge(true)
	\Moox\Jobs\JobsFailedPlugin::make()
	    ->label('Job failed')
	    ->pluralLabel('Jobs that failed hard')
	    ->enableNavigation(true)
	    ->navigationIcon('heroicon-o-face-frown')
	    ->navigationGroup('My Jobs and Queues')
	    ->navigationSort(5)
	    ->navigationCountBadge(true)
])
```

You don't need to register all Resources. If you don't use Job Batches, you can hide this feature by not registering it.

## Jobs for Beginners

Job queues are very useful. Every task that needs more than a couple of seconds can be handled in the background and Moox Jobs gives you full control in your applications UI. But starting with Laravel Job Queues needs some preparation.

The first decision depends on your hosting and deployment:

### Laravel Forge

Laravel Forge supports Redis, Horizon and Supervisor. The best way is to install Horizon and to enable it in the Forge UI. You can then schedule any job (or command dispatching your job).

To schedule any command without the need to change code (in kernel.php), you might consider using the [Filament Database Schedule plugin](https://filamentphp.com/plugins/husam-tariq-database-schedule).

More information:

-   [Laravel Forge docs: Queues](https://forge.laravel.com/docs/sites/queues.html)

### Shared Hosting

On most Shared Hosting and Managed Servers Redis and Supervisor are not available. The good thing: using Moox Jobs on shared hosts will perfectly work. Using the database queue-driver, you will be able to monitor and control your jobs and batches without pain.

You need SSH access to start the queue worker like this:

```bash
php artisan queue:work
```

and the Laravel Scheduler like this:

```bash
php artisan schedule:work
```

The best way, to automate your jobs (and care for re-running the queue:worker after failure), is to create a crontab to run the Laravel Scheduler minutely and to use the [Filament Database Schedule plugin](https://filamentphp.com/plugins/husam-tariq-database-schedule) to run your jobs (or commands).

More information:

-   [Laravel Queues for Beginners](https://sagardhiman021.medium.com/demystifying-queues-and-jobs-in-laravel-a-beginners-guide-with-examples-in-2023-a8e52698a298)
-   [Using Laravel Queues on Shared Hosting](https://talltips.novate.co.uk/laravel/using-queues-on-shared-hosting-with-laravel)

### Root Server

On a Root Server, VPS or Cloud Server Droplet the fastest way is to do job queuing like shared hosting. But as the combination Redis with Supervisor is much more stable and minimum twice as fast, you may also consider installing Redis and Supervisor manually using root privileges or (depending on your provider and deployment, maybe Forge, Envoyer or Ploi.io) a more convenient UI.

More information:

-   [Laravel Horizon on Ubuntu](https://dev.to/shuv1824/laravel-horizon-with-nginx-and-ubuntu-18-04-on-digitalocean-1fod)

### Laravel Vapor

On Laravel Vapor, the first-party deployment tool for going Serverless (using Amazon AWS Lambda Services), Laravel will automatically use Amazon SQS (Simple Queue Services) as queue driver. Laravel SQS is partly supported by Moox Jobs, means you can monitor jobs and failed jobs, retry failed jobs and use the progress feature. Pending jobs and batches are currently not implemented.

More information:

-   [Laravel Vapor Docs: Queues](https://docs.vapor.build/resources/queues.html)

When you got your job queues up and running, a good way to test Moox Jobs is using our

### Demo Job

You do not need to change anything in your Jobs to work with Filament Job Monitor. But especially for long running jobs you may find this example interesting:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;

class DemoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public function __construct()
    {
        $this->tries = 10;
        $this->timeout = 120;
        $this->maxExceptions = 3;
        $this->backoff = 240;
    }

    public function handle()
    {
        $count = 0;
        $steps = 10;
        $final = 100;

        while ($count < $final) {
            $this->setProgress($count);
            $count = $count + $steps;
            sleep(10);
        }
    }
}

```

Create a file named DemoJob.php in app/Jobs/ and copy over the contents above.

### Customize Job Name

By default, the name of the tasks comes from the name of the class. This can be rewritten based on the following example.

```php
use Illuminate\Support\Carbon;
...
public function displayName()
{
    $now = Carbon::now();
    return "Demo Job | Started: ".$now;
}

```

### Demo Job Command

This example command will start the job:

```php
<?php

namespace App\Console\Commands;

use App\Jobs\DemoJob;
use Illuminate\Console\Command;

class DemoJobCommand extends Command
{
    protected $signature = 'moox:demojob';

    protected $description = 'Start the Moox Demo Job';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Starting Moox Demo Job');

        DemoJob::dispatch();

        $this->info('Moox Demo Job finished');
    }
}

```

Create a file DemoJobCommand.php in app/Console/Commands. Then do a

```bash
php artisan moox:demojob
```

to dispatch one Demo Job.

Now you can monitor the progress of your job in the Filament UI.

## Progress

As shown in the Demo Job above, Moox Jobs comes with a progress feature. Using the JobProgress trait in your jobs is an optional thing. Jobs without the JobProgress-trait run and show up in the Moox Jobs UI, just missing the comfort of having the progress shown.

If you want to use the progress feature, be reminded that:

-   Your jobs will not run without Moox Jobs installed, when using the progress feature. If your jobs are part of an installable package, you should consider requiring Moox Jobs with your package.
-   If you want to remove Moox Jobs from your app, you have to remove the progress feature from your jobs prior to uninstalling Moox Jobs.
-   Coding the setProgress may not give an exact information about the progress. But especially for long running jobs it might be interesting to see where the job hangs (or just makes a long break). Debugging jobs without any glue about the progress may be much harder.

## Model

The database model for Moox Jobs is designed with [Vemto](https://vemto.app):

![jobs-model](https://github.com/mooxphp/moox/raw/main/art/vemto/moox-jobs.jpg)

## Authorization

We use Filament Shield instead, so that code is not heavily tested. Please leave a feedback, if you struggle.

If you would like to prevent certain users from accessing your page, you can register a policy:

```php
use App\Policies\JobMonitorPolicy;
use Moox\Jobs\Models\FailedJob;
use Moox\Jobs\Models\JobBatch;
use Moox\Jobs\Models\JobMonitor;

class AuthServiceProvider extends ServiceProvider
{
	protected $policies = [
		JobManager::class => JobManagerPolicy::class,
		FailedJob::class => FailedJobPolicy::class,
		JobBatch::class  => JobBatchPolicy::class,
	];
}
```

```php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FailedJobPolicy
{
	use HandlesAuthorization;

	public function viewAny(User $user): bool
	{
		return $user->can('manage_failed_jobs');
	}
}
```

same for FailedJobPolicy and JobBatchPolicy.

This will prevent the navigation item(s) from being registered.

## Scaling

While Laravels queue system and Laravel Horizon are made for spawning (specially batches) across multiple servers, Moox Jobs is not fully prepared for this kind of usage. The core strength of Moox Jobs is the Filament integration and the possibility to manage Jobs running on a non-Redis driver, not managing enterprise server farms.

Besides this, Moox Jobs is tested managing thousands of jobs per hour or millions of jobs per month.

![screenshot-batches](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-scale.jpg)

## Limitations

Moox Jobs is the perfect fit for the database queue driver. It runs fine on shared hostings and provides a job monitor, pending jobs, failed jobs and the possibility to retry failed jobs, where other Laravel packages like Horizon do not fit.

The job monitor and failed jobs are also working with Redis, SQS and Beanstalkd, but it does not show waiting jobs and job batches. For Redis we recommend using [Laravel Horizon](https://horizon.laravel.com), for Amazon SQS the AWS Dashboard. The solutions for Beanstalkd seem outdated, but you might give [Laravel Beanstalkd Admin UI](https://github.com/Dionera/laravel-beanstalkd-admin-ui) a try.

Another thing is using the Sync driver. As the Sync driver in Laravel is intended for development and testing, it executes jobs immediately (synchronously) and does not utilize queues. Therefore, it doesn't use the failed_jobs, jobs, or job_batches tables. Jobs are executed immediately within the same request lifecycle, so there's no queuing or storing of jobs. If a job fails, it's handled immediately within the execution context, not logged in the failed_jobs table. Jobs running with the sync driver may appear as running jobs and stay running forever, even if they are already completed or failed.

We plan to extend support for all queue drivers in the future. Watch the changelog.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Roadmap

As there are many parts (plugins) of Moox that need to be developed, there is no ETA on the following ... let's call them ideas.

### Stability

The planned next release focuses on stability:

-   [ ] Enable Logging with Moox Audit, extend demojob and others
-   [ ] Cleanup, Pruning, Detention (and config / docs for that) - https://github.com/croustibat/filament-jobs-monitor/commit/4616bb4b2f82d542cbbfe88d5143c9c43ec5196b
-   [ ] Improve Batches, see https://laravel.com/docs/10.x/queues#inspecting-batches and https://cosme.dev/post/how-to-handle-longrunning-jobs-in-laravel
-   [ ] Auto-handle the never-ending Jobs running with the sync driver
-   [ ] Maybe there is an easy way to support Jobs waiting with Redis?

### 3.1 UX

The planned V3.1 release will contain major UX improvements and a new dashboard:

-   [ ] Create a nicer UI (Charts, Apex?) and Dashboard (the Monitor)
-   [ ] Test progress bars
-   [ ] Instead of delete, it is better to Cancel jobs, mark them as Cancelled
-   [ ] Distinct between Failed (retry) and finally Failed or show retries left (like attempts show, but more obvious)
-   [ ] Improve Batches (Partly failing / non failing, see if job belongs to a batch, click-filter the jobs of a batch)
-   [ ] Failed job resource does not show the job name
-   [ ] Add filters and simplify usage
-   [ ] Configuration UX, in Navigation

### 3.2 Notifications

-   [ ] Notifications / Mailables for failed jobs and daily stats

### 3.2 Redis

-   [ ] Improve the support for Redis
-   [ ] Add "soft" dependencies to installer (Horizon or Predis?)

### 3.3 Queue Worker and Supervisor

-   [ ] Improve the support for queue workers and Supervisor

### 3.4 SQS

-   [ ] Improve the support for Amazon SQS or document
-   [ ] Add "soft" dependencies to installer (AWS SDK for PHP)

### 3.5 Beanstalkd

-   [ ] Improve the support for Beanstalkd or document

### 3.6 API, Scheduler, CRON

-   [ ] API
-   [ ] Scheduler (maybe an additional plugin)
-   [ ] CRON (maybe an additional plugin)

## Contribute

We welcome every contribution! It would be awesome, if you:

-   Create an Issue in this Repo that contains information about the problem or idea. We'll reply within a couple of days.
-   Create a Pull Request in the [Monorepo](https://github.com/mooxphp/moox). Please do not PR to our read-only repos, they are not prepared for code changes. Only the monorepo has quality gates and automated tests.
-   Translate Moox using [Weblate](https://hosted.weblate.org/engage/moox/).
-   Tell other people about Moox or link to us.
-   Consider a [donation or sponsorship](https://github.com/sponsors/mooxphp).

## Testing

Moox Jobs has currently no fully automated tests (besides Laravel Pint, PHPStan and Codacy as Quality Gates), but we are on the way to automate testing. We need two kind of tests:

1. The install and update commands
2. The installed application itself

and there are some things to consider:

-   Is Filament already installed or not? Our installer provides auto-installation including Filament.
-   Is there data to migrate? Our updater migrates existing data and needs demo data for this.
-   It is important to test different platforms (Linux, Mac, Windows), environments (Forge-Server, Shared Hosts, Local development) and queue drivers (Redis, Database, Sync, SQS, Beanstalkd)

### Test installation

This installation runs for a few hours on Forge, Vapor, Shared Hosting, Mac and Windows:

```bash
composer create-project laravel/laravel moox-test
cd moox-test
mysqladmin -u root -p create moox-test
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-user
composer require moox/jobs
php artisan mooxjobs:install
mkdir monorepo
cd monorepo
git clone https://github.com/mooxphp/moox
cp app/Jobs/* ../app/Jobs/
cp app/Console/Commands/* ../app/Console/Commands/
cp app/Console/kernel.php ../app/Console/kernel.php
# final steps depend on the target system
composer require laravel/horizon # Forge only
php artisan queue:work
php artisan schedule:work
```

### Test the update

```bash
composer create-project laravel/laravel moox-test
cd moox-test
mysqladmin -u root -p create moox-test
composer require filament/filament
php artisan filament:install --panels
php artisan make:filament-user
composer require moox/jobs:2.0.9
php artisan mooxjobs:install
mkdir monorepo
cd monorepo
git clone https://github.com/mooxphp/moox
mysql -u root -p moox-test < database/sql/jobs/v2/failed_jobs.sql
mysql -u root -p moox-test < database/sql/jobs/v2/job_batches.sql
mysql -u root -p moox-test < database/sql/jobs/v2/job_manager.sql
mysql -u root -p moox-test < database/sql/jobs/v2/jobs.sql
cd ..
composer require moox/jobs:3.0
composer update
php artisan mooxjobs:update
```

### Test installation with Filament install

```bash
composer create-project laravel/laravel moox-test
cd moox-test
mysqladmin -u root -p create moox-test
composer require filament/filament
composer require moox/jobs
php artisan mooxjobs:install
```

### Test installation with Filament require

```bash
composer create-project laravel/laravel moox-test
cd moox-test
mysqladmin -u root -p create moox-test
composer require moox/jobs
php artisan mooxjobs:install
```

### Cleanup after testing

```bash
mysql -u root -p drop database moox-test
cd ..
rm -Rf moox-test
```

## Sponsors

The initial development of Moox was sponsored by [heco gmbh, Germany](https://heco.de). A huge thank you for investing in Open Source!

If you use this plugin, please consider a small donation to keep this project under maintenance. Especially if it is a commercial project, it is pretty easy to calculate. A few bucks for a developer to build a great product or a hungry developer that produces bugs or - the worst case - needs to abandon the project. Yes, we are happy about every little sunshine in our wallet ;-)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

This Filament Plugin is heavily inspired (uses concept and / or code) from:

-   https://github.com/croustibat/filament-jobs-monitor
-   https://gitlab.com/amvisor/filament-failed-jobs

Both under MIT License.
A BIG thank you!!!
to the authors.
