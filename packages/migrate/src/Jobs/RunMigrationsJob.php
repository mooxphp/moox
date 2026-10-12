<?php

declare(strict_types=1);

namespace Moox\Migrate\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Moox\Migrate\Support\MigrateAuditor;
use Moox\Migrate\Support\MigrateRunner;
use Moox\Migrate\Support\MigrateRunStatus;

final class RunMigrationsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = MigrateRunner::LOCK_SECONDS;

    public int $tries = 1;

    public int $uniqueFor = MigrateRunner::LOCK_SECONDS;

    public function __construct(
        public mixed $userId,
        public ?string $userType,
        public int $pendingCount,
    ) {
    }

    public function uniqueId(): string
    {
        return MigrateRunner::LOCK_KEY;
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(MigrateRunner::LOCK_KEY))
                ->expireAfter(MigrateRunner::LOCK_SECONDS),
        ];
    }

    public function handle(MigrateRunner $runner): void
    {
        MigrateRunStatus::put([
            'state' => MigrateRunStatus::STATE_RUNNING,
            'title' => __('migrate::migrate.migrate_running'),
            'body' => null,
            'exit_code' => null,
            'pending_count' => $this->pendingCount,
            'user_id' => $this->userId,
            'user_type' => $this->userType,
        ]);

        $result = $runner->execute();

        $state = match (true) {
            $result['busy'] => MigrateRunStatus::STATE_BUSY,
            $result['ok'] => MigrateRunStatus::STATE_DONE,
            default => MigrateRunStatus::STATE_FAILED,
        };

        MigrateRunStatus::put([
            'state' => $state,
            'title' => $result['title'],
            'body' => $result['body'],
            'exit_code' => $result['exit_code'],
            'pending_count' => $this->pendingCount,
            'user_id' => $this->userId,
            'user_type' => $this->userType,
        ]);

        MigrateAuditor::log(
            $this->resolveCauser(),
            $result['ok'],
            $result['busy'],
            $result['exit_code'],
            $this->pendingCount,
        );
    }

    private function resolveCauser(): ?Model
    {
        if ($this->userType === null || $this->userId === null || $this->userType === '') {
            return null;
        }

        if (! class_exists($this->userType) || ! is_subclass_of($this->userType, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $type */
        $type = $this->userType;

        return $type::query()->find($this->userId);
    }
}
