<?php

declare(strict_types=1);

namespace Moox\Transform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Moox\Transform\Contracts\PreparesTransformSource;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformRunner;

class RunTransformRecordJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 0;

    public function __construct(
        private readonly int $transformRecordId,
        private readonly ?int $actorId = null,
    ) {
        $timeout = config('transform.job_timeout', 0);
        $this->timeout = is_int($timeout) ? $timeout : 0;
        $this->onQueue((string) config('transform.job_queue', 'transform'));
    }

    public static function dispatchForActor(int $transformRecordId, ?int $actorId = null): void
    {
        self::dispatch($transformRecordId, $actorId ?? self::configuredActorId() ?? self::currentActorId());
    }

    public function handle(TransformRunner $runner): void
    {
        $record = TransformRecord::query()->find($this->transformRecordId);
        if (! $record instanceof TransformRecord) {
            return;
        }

        $authenticated = $this->loginActor();

        try {
            $this->prepareSource($record);
            $runner->run($record);
        } finally {
            if ($authenticated) {
                Auth::logout();
            }
        }
    }

    public static function configuredActorId(): ?int
    {
        $email = config('transform.actor_email');
        if (! is_string($email) || $email === '') {
            return null;
        }

        $model = config('auth.providers.users.model');
        if (! is_string($model) || $model === '' || ! is_subclass_of($model, Model::class)) {
            return null;
        }

        $id = $model::query()->where('email', $email)->value(
            $model::query()->getModel()->getKeyName(),
        );

        return is_numeric($id) ? (int) $id : null;
    }

    public static function currentActorId(): ?int
    {
        $actorId = Auth::id();

        if (is_int($actorId)) {
            return $actorId;
        }

        if (is_string($actorId) && ctype_digit($actorId)) {
            return (int) $actorId;
        }

        return null;
    }

    private function prepareSource(TransformRecord $record): void
    {
        $preparer = config('transform.source_preparer');
        if (! is_string($preparer) || $preparer === '' || ! is_a($preparer, PreparesTransformSource::class, true)) {
            return;
        }

        app($preparer)->prepare($record);
        $record->refresh();
    }

    private function loginActor(): bool
    {
        if ($this->actorId === null || Auth::user() !== null) {
            return false;
        }

        $model = config('auth.providers.users.model');
        if (! is_string($model) || $model === '' || ! is_subclass_of($model, Model::class)) {
            return false;
        }

        $actor = $model::query()->find($this->actorId);
        if (! $actor instanceof Authenticatable) {
            return false;
        }

        Auth::login($actor);

        return true;
    }
}
