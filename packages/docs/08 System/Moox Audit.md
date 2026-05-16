# Idea



Start with retention of soft deletes and logs

- Delete jobs to flush
- Pending_desctruction ... see https://laravel-news.com/soft-deletes



Use:

-   [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)

see existing plugins

-   [adrolli/filament-spatie-laravel-activitylog](https://github.com/adrolli/filament-spatie-laravel-activitylog) - to be archived then
-   [oriondevelops/filament-activity-log](https://github.com/oriondevelops/filament-activity-log)
-   [shuvroroy/filament-spatie-laravel-activitylog](https://github.com/shuvroroy/filament-spatie-laravel-activitylog)
-   [Z3d0X/filament-logger](https://github.com/Z3d0X/filament-logger) // [z3d0x-logger](https://filamentphp.com/plugins/z3d0x-logger)
-   [pxlrbt/filament-activity-log](https://github.com/pxlrbt/filament-activity-log) forked [noxoua-activity-log](https://filamentphp.com/plugins/noxoua-activity-log)

distinguish between following:

-   Model changes
-   User actions
-   Mail tracking - https://github.com/backstagephp/laravel-mails 
-   Jobs tracking - is inside Moox Jobs
-   Scheduled tasks
-   Other (system messages)
-   Laravel logs (out of scope, I guess)

[laravel-auditing](https://laravel-auditing.com/) and [filament-auditing](https://github.com/TappNetwork/filament-auditing) (pretty interesting ... adds a Resource Manager to a Model)



### **Use Spatie as-is for MySQL, Redis as a buffer**

This is likely the **most stable and practical** option:

- Keep Spatie/Auditing writing to MySQL
- Buffer writes via queue or Redis first
- Batch insert to avoid high connection pressure





## Moox Rewind

- [mansoor-versionable](https://filamentphp.com/plugins/mansoor-versionable) uses [overtrue/laravel-versionable](https://github.com/overtrue/laravel-versionable), not Spatie, but the UI is nice
- [rmsramos/activitylog](https://github.com/rmsramos/activitylog) - nice looking timeline view



## Moox Analytics

https://github.com/panphp/pan

https://github.com/andreaselia/laravel-analytics

https://posthog.com/blog/best-open-source-analytics-tools



## Moox Buffer

Used by all

Yes — that’s a **very clean and modular architecture.**



You’re defining each concern with clear separation, and introducing **Moox Buffer** as the shared layer for performance and reliability. Let’s formalize your plan:



------





## **✅ Moox Suite Architecture**



| **Package**    | **Purpose**                            | **Backing Technology**               | **Queue/Buffer?**           |
| -------------- | -------------------------------------- | ------------------------------------ | --------------------------- |
| Moox Audit     | Log **user/system actions**            | spatie/laravel-activitylog           | ✅ Uses Moox Buffer to defer |
| Moox Rewind    | Log **model versioning (time travel)** | overtrue/laravel-versionable         | ⚠️ Optional (usually sync)   |
| Moox Analytics | Log **high-volume app events**         | ❌ Custom (event log system)          | ✅ Heavy use of Moox Buffer  |
| Moox Buffer    | Central **buffering + batching layer** | Redis Streams / List + Laravel Queue | 🧠 Core piece                |



------





## **🧠 Why This Makes Sense**







### **🔄 Moox Buffer as a Shared Service**





Moox Buffer is your:



- **Write firewall** — keeps MySQL safe during traffic spikes
- **Performance layer** — smooths out load
- **Unifier** — lets all Moox packages work async and independently





------





## **🔧 What Moox Buffer Should Do**



| **Responsibility**           | **Detail**                                             |
| ---------------------------- | ------------------------------------------------------ |
| Queue logs/events            | Redis::xadd() or rpush()                               |
| Support channels/types       | audit, analytics, version, etc.                        |
| Flush logs periodically      | Scheduled FlushMooxLogs job                            |
| Write to appropriate store   | Audit → MySQL (activity_log), Analytics → custom table |
| Handle bulk insert + retries | So queues don’t blow up                                |
| Optional: fallback to file   | If Redis down, optionally log to disk for recovery     |



------





## **📦 Example Usage in Other Packages**







### **🔹 Moox Audit (Spatie)**



```
MooxBuffer::push('audit', [
    'user_id' => 123,
    'action' => 'deleted project',
    'subject_type' => 'Project',
    'subject_id' => 42,
    'timestamp' => now(),
]);
```



### **🔹 Moox Rewind (Overtrue)**



https://github.com/oddvalue/laravel-drafts see this before deciding for overtrue, also a draft system that look nice

Usually writes directly to DB, but **could** push version data into buffer:

```
MooxBuffer::push('version', [
    'model' => 'Post',
    'model_id' => 12,
    'diff' => [...],
]);
```



### **🔹 Moox Analytics**



```
MooxBuffer::push('analytics', [
    'event' => 'opened_feature_modal',
    'user_id' => 456,
    'properties' => [...],
    'session_id' => 'abc-123',
]);
```



------





## **🧱 Suggested Design**







### **MooxBuffer**

###  **Class**



```
class MooxBuffer
{
    public static function push(string $channel, array $payload): void
    {
        Redis::rpush("moox:buffer:$channel", json_encode($payload));
    }

    public static function popBatch(string $channel, int $limit = 100): array
    {
        $items = [];
        for ($i = 0; $i < $limit; $i++) {
            $item = Redis::lpop("moox:buffer:$channel");
            if (!$item) break;
            $items[] = json_decode($item, true);
        }
        return $items;
    }
}
```

Then write Laravel Schedule jobs per channel:

```
php artisan moox:flush-buffer audit
php artisan moox:flush-buffer analytics
```

Each job pops 100–1000 items and bulk-inserts them.



------





## **🔖 Summary**



| **Package**    | **Writes to**                                                | **Buffered via**     |
| -------------- | ------------------------------------------------------------ | -------------------- |
| Moox Audit     | activity_log table                                           | ✅ MooxBuffer         |
| Moox Rewind    | versionable_versions, also see https://github.com/oddvalue/laravel-drafts | ⚠️ Maybe direct       |
| Moox Analytics | analytics_events                                             | ✅ MooxBuffer         |
| Moox Buffer    | Redis List/Stream                                            | 🧠 Used by all others |



------



Would you like me to generate a MooxBuffer starter class + a sample flush job command + Redis config? You could drop it into any Laravel app.