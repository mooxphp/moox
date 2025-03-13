# Idea

-   The jobs monitor model needs to be enhanced
    -   instead of using the db, it should be able to use redis
    -   it needs to be redis compatible
    -   jobs relation
    -   failed jobs relation
    -   batches relation
-   The jobs monitor resource needs to be completely refactored
    -   Use Moox config with tabs
        -   Monitor
-   All other resources can be deleted

So first the Redis problems. There are two:

-   running Moox Jobs on DB works fine for small installations, but when heavy load apps use Moox Jobs, the DB gets in trouble. Using Redis should solve this problem.
-   Moox Jobs is currently able to get the state of the job correct, when running on db driver, on redis, sync and most probably all other drivers it never get's the job 'done'

## Links

-   [romanzipp/Laravel-Queue-Monitor](https://github.com/romanzipp/Laravel-Queue-Monitor/)
-   [formulae.brew.sh/formula/supervisor](https://formulae.brew.sh/formula/supervisor)
-   [laravel.com/docs/10.x/horizon](https://laravel.com/docs/10.x/horizon)
-   [stephenjude/filament-debugger](https://github.com/stephenjude/filament-debugger)
-   Use [supervisord](http://supervisord.org/) on [http://localhost:9001](http://localhost:9001/)
-   [suren1986/laravel-supervisor-dashboard](https://github.com/suren1986/laravel-supervisor-dashboard)
-   [mlazarov/supervisord-monitor](https://github.com/mlazarov/supervisord-monitor)
-   [bigdataplot/supervisord-monitor@`master`](https://github.com/bigdataplot/supervisord-monitor/tree/master?rgh-link-date=2023-09-07T21%3A48%3A15Z)
-   [pierophp/laravel-queue-manager](https://github.com/pierophp/laravel-queue-manager)
-   https://github.com/Dionera/laravel-beanstalkd-admin-ui
-   https://github.com/stephenjude/filament-debugger
-   AWS SDK for PHP
-   Beanstalkd package (see readme)

## Invoke from Frontend

Moox Jobs allows to automatically invoke Jobs, without the need of a scheduler task or cron. We use Frontend requests for this. Like WordPress does, but with a much more robust system, the Laravel Job System.

That feature needs requests, that means:

-   When nobody uses the website, app or API, no Jobs will run
-   You cannot guarantee that a Job will run after a certain time

If both is no problem, you can use the following config to activate this feature:

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Invoke scheduled tasks from frontend
    |--------------------------------------------------------------------------
    |
    | Moox Jobs allows to automatically invoke Jobs, without the need of a
    | scheduler task or cron. We use Frontend requests for this. Like WP
    | does, but with a much more robust system, the Laravel Job System.
    |
    */
    'invoke_scheduled_tasks_from_frontend' => false,

    /*
    |--------------------------------------------------------------------------
    | Invoke only from dedicated URL
    |--------------------------------------------------------------------------
    |
    | If you want to invoke scheduled tasks only from a dedicated URL,
    | you can set this to true. You may use a Ping service then.
    | By default, every request will trigger the jobs.
    |
    */
    'invoke_only_from_dedicated_url' => false,
    'dedicated_url' => '/moox/heartbeat',

    /*
    |--------------------------------------------------------------------------
    | Scheduled jobs
    |--------------------------------------------------------------------------
    |
    | Here you can define which jobs should be invoked at
    | certain intervals. The key is the job class and
    | the value is the interval in minutes.
    |
    */
    'scheduled_jobs' => [
        \App\Jobs\ProcessScheduledDeletions::class => 60, // Every 60 minutes
        \App\Jobs\SyncExternalData::class => 30, // Every 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Atomic lock
    |--------------------------------------------------------------------------
    |
    | Specially on high load systems, you may want to invoke scheduled tasks
    | with an atomic lock. This will prevent that multiple instances
    | of the scheduled task will run at the same time.
    |
    */
    'invoke_atomic_lock' => true,
    'invoke_atomic_lock_timeout' => 60, // 60 seconds

];
```

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class InvokeScheduledJobs
{
    public function handle($request, Closure $next)
    {
        if (!Config::get('moox.invoke_scheduled_tasks_from_frontend')) {
            return $next($request);
        }

        $scheduledJobs = Config::get('moox.scheduled_jobs', []);

        foreach ($scheduledJobs as $jobClass => $interval) {
            $cacheKey = 'moox_scheduled_job_' . md5($jobClass);

            $lock = Cache::lock($cacheKey, $interval * 60);

            if ($lock->get()) {
                dispatch(new $jobClass());

                $lock->release();
            }
        }

        return $next($request);
    }
}
```
