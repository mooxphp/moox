![Moox Jobs](https://github.com/mooxphp/moox/raw/main/art/banner/jobs.jpg)

# Moox Jobs

Managing Job Queues, Failed Jobs and Batches in Filament.

Alternative to Laravel Horizon, if you use the database driver for queues. Nice addon to Laravel Horizon, if you use Redis. See Limitations below for more information.

## Upgrading from Moox Jobs V2

Moox Jobs V3 changes the database schema. We made an convenient update command for you:

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

Curious what the install command does? See manual installation below.

## Features

### Jobs

Monitor your running and finished jobs:

![screenshot-jobs](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-jobs.jpg)

This table includes auto-pruning (7 days retention, configurable).

### Jobs waiting

See all waiting Jobs queued, kill one or many:

![screenshot-waiting](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-waiting.jpg)

### Jobs failed

See all failed Jobs including details, retry or delete:

![screenshot-details](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-details.jpg)

![screenshot-detail](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-detail.jpg)

### Job batches

See your job batches, prune batches:

![screenshot-batches](https://github.com/mooxphp/moox/raw/main/art/screenshot/jobs-batches.jpg)

## Manual Installation

This Laravel package is made for Filament 3 and the awesome TALL-Stack. If you don't want to use our install command, follow thes manual steps to install the package.

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

# Queue tables, if using the database driver instead of Redis queue backend
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

## Usage

Start your queue with `php artisan queue:work`, run a Background Job (use following example, if you need one) and go to the route

-   `/admin/jobs` to see the jobs running and done
-   `/admin/waiting-jobs` to see or delete waiting jobs
-   `/admin/failed-jobs` to see, retry or delete failed jobs
-   `/admin/job-batches` to see job batches, or prune the batch table

## Example Job

You do not need to change anything in your Jobs to work with Filament Job Monitor. But especially for long running jobs you may find this example interesting:

```php
<?php

namespace App\Jobs;

use Moox\Jobs\Traits\JobProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use
class JobMonitorDemo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, QueueProgress;

    public function __construct()
    {
        //
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

Now you can monitor the progress of your job in the Filament UI.

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

## Limitations

Moox Jobs is the perfect fit for the database queue driver. It runs fine on shared hostings and provides a job monitor, pending jobs, failed jobs and the possibility to retry failed jobs, where all other Laravel packages do not fit.

The job monitor and failed jobs are also working with Redis, SQS and Beanstalkd, but it does not show waiting jobs and there might be problems with job batches. For Redis we recommend using [Laravel Horizon](https://horizon.laravel.com), for Amazon SQS the AWS Dashboard. The solutions for Beanstalkd seem outdated, but you might give [Laravel Beanstalkd Admin UI](https://github.com/Dionera/laravel-beanstalkd-admin-ui) a try.

Another thing is using the Sync driver. As the Sync driver in Laravel is intended for development and testing, it executes jobs immediately (synchronously) and does not utilize queues. Therefore, it doesn't use the failed_jobs, jobs, or job_batches tables. Jobs are executed immediately within the same request lifecycle, so there's no queuing or storing of jobs. If a job fails, it's handled immediately within the execution context, not logged in the failed_jobs table. Jobs running with the sync driver may appear as running jobs and stay running forever, even if they are already completed or failed.

We plan to extend support for all queue drivers in the future. Watch the changelog.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contribute

We welcome every contribution! It would be awesome, if you:

-   Create an Issue in this Repo that contains information about the problem or idea. We'll reply within a couple of days.
-   Create a Pull Request in the [Monorepo](https://github.com/mooxphp/moox). Please do not PR to our read-only repos, they are not prepared for code changes. Only the monorepo has quality gates and automated tests.
-   Translate Moox using [Weblate](https://hosted.weblate.org/engage/moox/).
-   Tell other people about Moox or link to us.
-   Consider a [donation or sponsorship](https://github.com/sponsors/mooxphp).

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
