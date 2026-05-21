# Moox Demo

Demo jobs and Artisan commands for testing [Moox Jobs](../jobs/README.md) (progress, batches, failures, timeouts).

Copy the jobs and commands into your Laravel app (`app/Jobs/`, `app/Console/Commands/`), then register the schedule in `app/Console/Kernel.php` (or your scheduler of choice).

## What each job simulates

### DemoJob (`moox:demojob`)

A long-running job with a progress bar (`JobProgress`): 10 steps of 10% each, with a 10-second pause between steps — useful for seeing “Running” jobs and progress in Filament.

### BatchJob (`moox:batchjob`)

Dispatches a Laravel job batch with 14× `ShortJob` — exercises the Batches view in Moox Jobs.

### ShortJob

A fast job with progress, batch-aware (exits early if the batch was cancelled).

### LongJob (`moox:longjob`)

A very long run (20 seconds per percentage step, 1200 s timeout) — simulates a slow or “stuck” long-running job.

### FailJob (`moox:failjob`)

Throws an exception on purpose — ends up in Failed Jobs; useful for testing retries and backoff.

### TimeoutJob (`moox:timeoutjob`)

10 s timeout but runs longer (progress + `sleep`) — useful for testing timeout behavior.

Each job sets `$tries`, `$timeout`, and `$backoff` so you can also observe retry logic in the UI.

## Commands

| Command | Description |
| --- | --- |
| `moox:demojob` | Dispatch the demo job (progress) |
| `moox:batchjob` | Dispatch the batch job |
| `moox:failjob` | Dispatch a job that fails |
| `moox:longjob` | Dispatch a long-running job |
| `moox:shortjob` | Dispatch a short job |
| `moox:timeoutjob` | Dispatch a job that times out |

Run manually, for example:

```bash
php artisan moox:demojob
```

## Schedule

Add this to the `schedule()` method in `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('moox:batchjob')->daily();
    $schedule->command('moox:demojob')->hourly();

    // Optional demo schedules (uncomment as needed):
    // $schedule->command('moox:failjob')->cron('0 */3 * * *');        // Every 3 minutes
    // $schedule->command('moox:longjob')->cron('0 */45 * * *');       // Every 45 minutes
    // $schedule->command('moox:timeoutjob')->cron('0 */20 * * *');    // Every 20 minutes
}
```

Ensure the Laravel scheduler runs (`php artisan schedule:work` locally, or a cron entry for `schedule:run` in production).
