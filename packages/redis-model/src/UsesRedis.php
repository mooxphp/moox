<?php

namespace Moox\RedisModel;

use Illuminate\Support\Facades\Redis;

trait UsesRedis
{
    /**
     * Determine if Redis is enabled for the model.
     */
    protected function isRedisEnabled(): bool
    {
        return property_exists($this, 'useRedis') && $this->useRedis === true;
    }

    /**
     * Save the model instance by storing it in Redis.
     */
    public function save(array $options = [])
    {
        if (! $this->isRedisEnabled()) {
            return parent::save($options);
        }

        $key = $this->getRedisKey();
        Redis::set($key, json_encode($this->attributes));

        return true;
    }

    /**
     * Delete the model instance by removing it from Redis.
     */
    public function delete()
    {
        if (! $this->isRedisEnabled()) {
            return parent::delete();
        }

        Redis::del($this->getRedisKey());

        return true;
    }

    /**
     * Retrieve a model by its primary key.
     */
    public static function find($id)
    {
        $instance = new static;

        if (! $instance->isRedisEnabled()) {
            return parent::find($id);
        }

        $key = $instance->getRedisKey($id);
        $data = Redis::get($key);

        return $data ? new static(json_decode($data, true)) : null;
    }

    /**
     * Generate a Redis key for the model.
     */
    protected function getRedisKey($id = null)
    {
        $id = $id ?: $this->{$this->getKeyName()};

        return 'model:'.static::class.':'.$id;
    }
}
