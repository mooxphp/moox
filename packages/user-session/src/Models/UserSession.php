<?php

namespace Moox\UserSession\Models;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class UserSession extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sessions';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'user_type',
        'user_id',
        'device_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity',
        'whitelisted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
        'payload' => 'array',
        'last_activity' => 'integer',
        'whitelisted' => 'boolean',
    ];

    /**
     * Get the owning user model.
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Resolve the related user without breaking when `user_type` is null
     * (Laravel's default session writer only stores `user_id`).
     */
    public function resolveUser(): ?Model
    {
        if ($this->relationLoaded('user')) {
            /** @var Model|null $user */
            $user = $this->getRelation('user');

            return $user;
        }

        if (blank($this->user_id)) {
            return null;
        }

        $modelClass = filled($this->user_type) && is_string($this->user_type) && class_exists($this->user_type)
            ? $this->user_type
            : config('auth.providers.users.model');

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return null;
        }

        $user = $modelClass::query()->find($this->user_id);
        $this->setRelation('user', $user);

        return $user;
    }

    /**
     * Resolve an optional user-device row when that package is installed.
     */
    public function resolveDevice(): ?Model
    {
        if ($this->relationLoaded('device')) {
            /** @var Model|null $device */
            $device = $this->getRelation('device');

            return $device;
        }

        if (blank($this->device_id)) {
            return null;
        }

        $modelClass = self::optionalDeviceModelClass();

        if ($modelClass === null) {
            return null;
        }

        $device = $modelClass::query()->find($this->device_id);
        $this->setRelation('device', $device);

        return $device;
    }

    /**
     * Eager-hydrate user + optional device models for a page of session rows (avoids N+1).
     *
     * @param  Collection<int, mixed>|EloquentCollection<int, mixed>  $sessions
     */
    public static function hydrateRelations(Collection|EloquentCollection $sessions): void
    {
        $sessions = Collection::make($sessions)->filter(fn (mixed $session): bool => $session instanceof self);

        if ($sessions->isEmpty()) {
            return;
        }

        $groupedByType = $sessions
            ->filter(fn (self $session): bool => filled($session->user_id))
            ->groupBy(function (self $session): string {
                if (filled($session->user_type) && is_string($session->user_type) && class_exists($session->user_type)) {
                    return $session->user_type;
                }

                return (string) config('auth.providers.users.model');
            });

        foreach ($groupedByType as $modelClass => $group) {
            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                foreach ($group as $session) {
                    $session->setRelation('user', null);
                }

                continue;
            }

            /** @var Model $model */
            $model = new $modelClass;
            $keyName = $model->getKeyName();
            $users = $modelClass::query()
                ->whereIn($keyName, $group->pluck('user_id')->unique()->filter()->all())
                ->get()
                ->keyBy($keyName);

            foreach ($group as $session) {
                $session->setRelation('user', $users->get($session->user_id));
            }
        }

        $deviceModel = self::optionalDeviceModelClass();

        if ($deviceModel === null) {
            return;
        }

        $deviceIds = $sessions->pluck('device_id')->filter()->unique()->values();

        if ($deviceIds->isEmpty()) {
            foreach ($sessions as $session) {
                $session->setRelation('device', null);
            }

            return;
        }

        /** @var Model $deviceInstance */
        $deviceInstance = new $deviceModel;
        $deviceKey = $deviceInstance->getKeyName();

        $devices = $deviceModel::query()
            ->whereIn($deviceKey, $deviceIds->all())
            ->get()
            ->keyBy($deviceKey);

        foreach ($sessions as $session) {
            $session->setRelation(
                'device',
                filled($session->device_id) ? $devices->get($session->device_id) : null
            );
        }
    }

    /**
     * @return class-string<Model>|null
     */
    protected static function optionalDeviceModelClass(): ?string
    {
        $class = 'Moox\\UserDevice\\Models\\UserDevice';

        return class_exists($class) ? $class : null;
    }
}
