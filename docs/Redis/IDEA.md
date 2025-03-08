# Idea

Idea came up because of performance issues with Moox Jobs.

When creating a job, there is much load on the database. That is primarily because of logging things and changing the status of the job.

So, the idea is to use Redis to store the jobs and to use the Redis-Queue.

See:

-   [alvin0/redis-model](https://github.com/alvin0/redis-model)
-   [bilaliqbalr/laravel-redis](https://github.com/bilaliqbalr/laravel-redis)

Means we need to solve two major problems:

-   [ ] Create a Redis-Entity for Filament + Redis
-   [ ] Use it for Status and Logging, have kind of relations
