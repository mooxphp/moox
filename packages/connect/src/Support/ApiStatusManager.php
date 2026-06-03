<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class ApiStatusManager
{
    public const STATUS_NEW = 'new';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ERROR = 'error';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_UNUSED = 'unused';

    private string $cachePrefix;

    private int $errorThreshold;

    private int $unusedThreshold;

    public function __construct(
        string $cachePrefix = 'api_status:',
        int $errorThreshold = 5,
        int $unusedThreshold = 30
    ) {
        $this->cachePrefix = $cachePrefix;
        $this->errorThreshold = $errorThreshold;
        $this->unusedThreshold = $unusedThreshold;
    }

    public function setStatus(string $apiId, string $status): void
    {
        if (! $this->isValidStatus($status)) {
            throw new RuntimeException("Invalid status: {$status}");
        }

        Cache::put(
            $this->getStatusKey($apiId),
            [
                'status' => $status,
                'updated_at' => Carbon::now()->timestamp,
            ],
            Carbon::now()->addMonth()
        );
    }

    public function getStatus(string $apiId): string
    {
        $data = Cache::get($this->getStatusKey($apiId));

        if ($data === null) {
            return self::STATUS_NEW;
        }

        // Check if API is unused
        if ($data['status'] === self::STATUS_ACTIVE) {
            $lastUsed = Cache::get($this->getLastUsedKey($apiId));
            if ($lastUsed && Carbon::createFromTimestamp($lastUsed)
                ->diffInDays(Carbon::now()) > $this->unusedThreshold) {
                $this->setStatus($apiId, self::STATUS_UNUSED);

                return self::STATUS_UNUSED;
            }
        }

        return $data['status'];
    }

    public function recordUsage(string $apiId): void
    {
        Cache::put(
            $this->getLastUsedKey($apiId),
            Carbon::now()->timestamp,
            Carbon::now()->addMonth()
        );

        if ($this->getStatus($apiId) !== self::STATUS_ERROR) {
            $this->setStatus($apiId, self::STATUS_ACTIVE);
        }
    }

    public function recordError(string $apiId): void
    {
        $key = $this->getErrorKey($apiId);
        $errors = Cache::get($key, []);

        $errors[] = Carbon::now()->timestamp;
        $errors = array_filter(
            $errors,
            fn ($timestamp) => Carbon::createFromTimestamp($timestamp)
                ->isAfter(Carbon::now()->subDay())
        );

        Cache::put($key, $errors, Carbon::now()->addDay());

        if (count($errors) >= $this->errorThreshold) {
            $this->setStatus($apiId, self::STATUS_ERROR);
        }
    }

    public function enable(string $apiId): void
    {
        $this->setStatus($apiId, self::STATUS_ACTIVE);
        $this->clearErrors($apiId);
    }

    public function disable(string $apiId): void
    {
        $this->setStatus($apiId, self::STATUS_DISABLED);
    }

    public function clearErrors(string $apiId): void
    {
        Cache::forget($this->getErrorKey($apiId));
    }

    private function getStatusKey(string $apiId): string
    {
        return "{$this->cachePrefix}{$apiId}:status";
    }

    private function getErrorKey(string $apiId): string
    {
        return "{$this->cachePrefix}{$apiId}:errors";
    }

    private function getLastUsedKey(string $apiId): string
    {
        return "{$this->cachePrefix}{$apiId}:last_used";
    }

    private function isValidStatus(string $status): bool
    {
        return in_array($status, [
            self::STATUS_NEW,
            self::STATUS_ACTIVE,
            self::STATUS_ERROR,
            self::STATUS_DISABLED,
            self::STATUS_UNUSED,
        ], true);
    }
}
