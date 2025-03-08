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

Installer:

-   Filament installer then register OK
-   Plugin section missing FAIL
-   Plugin section already there FAIL

Updater:

-   labels should be lowercased

-   [ ] Installer updater must require moox core
-   [ ] moox core dev-main is good for dev, but not for prod
-   [ ] UI see https://demo.filamentphp.com/shop/orders
-   [ ] DailyStats Entity
    -   [ ] day
    -   [ ] total_jobs (displaying jobs per day/hour/minute)
    -   [ ] total_batches (displaying batches per)
    -   [ ] jobs_succeeded
    -   [ ] jobs_failed
    -   [ ] average_execution_time
    -   [ ] average_waiting_time

## Fast start

-   https://github.com/mooxphp/jobs
-   https://moox.test/moox/
-   https://laraver.se/admin
-   http://49.12.191.84/admin
-   https://chat.openai.com/c/7ba19fdf-22f3-4664-b586-358a2fbec5a6
-   https://laravel.com/docs/10.x/queues

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
