<?php

namespace Moox\RedisModel;

use Exception;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Moox\RedisModel\Exceptions\ErrorTransactionException;

class RedisRepository
{
    /**
     * @var PhpRedisConnection
     */
    protected $connection;

    public function __construct(PhpRedisConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get connection
     */
    public function getConnection(): PhpRedisConnection
    {
        return $this->connection;
    }

    /**
     *Gets the Redis prefix for keys used by the Redis model.
     *The prefix is determined by looking at the redis_model_options.prefix configuration value first,
     *Then falling back to the database.redis.options.prefix configuration value.
     *
     * @return string The Redis key prefix for this model.
     */
    public function getRedisPrefix()
    {
        $defaultPrefix = config('database.redis.options.prefix', 'redis_model_');

        return config('redis-model.redis_model_options.prefix', $defaultPrefix);
    }

    /**
     * Retrieves all Redis keys matching a given pattern, after removing the database prefix.
     *
     * @param  string|null  $hash  The pattern to match the Redis keys against, or null to match all keys.
     * @return array An array of Redis keys matching the pattern, with the database prefix removed.
     */
    public function getHashByPattern(?string $hash)
    {
        return self::removeSlugDatabaseFromRedisKeys($this->getConnection()->keys($hash), $this->getRedisPrefix());
    }

    /**
     * Counts the number of Redis hash keys that match the given pattern.
     *
     * @param  string  $pattern  The Redis hash key pattern to match.
     * @return int The number of Redis hash keys that match the given pattern
     */
    public function countByPattern(string $pattern): int
    {
        return count($this->getHashByPattern($pattern));
    }

    /**
     * Fetches all fields and their values for a given Redis hash key
     *
     * @param  string  $hash  The Redis hash key
     * @return array An array containing all fields and their corresponding values for the given Redis hash key
     */
    public function fetchProperByHash($hash)
    {
        return $this->getConnection()->hGetAll($hash);
    }

    /**
     * Retrieves hash data from multiple Redis keys using a pipeline approach and returns it in an associative array
     *
     * @param  array  $keys  An array of Redis keys
     * @return array An associative array containing hash data retrieved from multiple Redis keys
     */
    public function fetchProperByListHash(array $keys)
    {
        $result = [];

        $fetch = $this->getConnection()->pipeline(function ($pipe) use ($keys) {
            foreach (self::removeSlugDatabaseFromRedisKeys($keys, $this->getRedisPrefix()) as $key) {
                $pipe->hGetAll($key);
            }
        });

        foreach ($keys as $cursor => $key) {
            $result[$key] = $fetch[$cursor];
        }

        return $result;
    }

    /**
     * Retrieves all data from Redis hashes that match the given pattern.
     *
     * @param  string  $pattern  The pattern to match against Redis hashes.
     * @return array The data from Redis hashes that match the given pattern.
     */
    public function fetchHashDataByPattern(string $pattern)
    {
        $keys = $this->getHashByPattern($pattern);

        foreach ($keys as $key) {
            $data[$key] = $this->fetchProperByHash($key);
        }

        return $data ?? [];
    }

    /**
     * Inserts a Redis hash with the given key and data.
     *
     * @param  string  $key  The key of the Redis hash.
     * @param  array  $data  An associative array of fields and their values.
     * @return bool|int Returns `true` on success, `false` on failure.
     */
    public function insertRedisHashes(string $key, array $data)
    {
        return $this->getConnection()->hMSet($key, $data);
    }

    /**
     * Update the values of a Redis hash with the given data, and optionally rename the hash.
     *
     * @param  string  $oldHash  The name of the Redis hash to update.
     * @param  string  $newHash  The new name for the Redis hash. If set, the old hash will be renamed to this new name.
     * @return bool True on success, false on failure.
     */
    public function renameRedisHash(string $oldHash, string $newHash)
    {
        return $this->getConnection()->rename($oldHash, $newHash);
    }

    /**
     * Update the data of a Redis hash.
     *
     * @param  string  $oldHash  The old hash key to update.
     * @param  array  $data  An associative array containing the field-value pairs to update.
     * @param  string|null  $newHash  The new hash key to rename the old hash key to, if provided.
     * @return bool Returns true if the update was successful, false otherwise.
     */
    public function updateRedisHashes(string $oldHash, array $data, ?string $newHash = null)
    {
        return $this->transaction(function ($conTransaction) use ($oldHash, $newHash, $data) {
            try {
                if ($newHash != null && $oldHash != $newHash) {
                    $conTransaction->rename($oldHash, $newHash);
                }

                $conTransaction->hMSet($newHash, $data);

                return true;
            } catch (Exception $e) {
                // Todo
                //$transaction->discard();

                return false;
            }
        });
    }

    /**
     * Insert multiple Redis hashes with key-value pairs in bulk
     *
     * @param  array  $hashes  Array of Redis hashes with key-value pairs to insert in bulk.
     *                         Format: [Key => [Field => Value, ...], ...]
     * @return bool Returns true if all hashes were inserted successfully, false otherwise.
     */
    public function insertMultipleRedisHashes(array $hashes)
    {
        foreach ($hashes as $key => $data) {
            $this->getConnection()->hMSet($key, $data);
        }

        return true;
    }

    /**
     * Destroy a hash from Redis by given key or keys.
     *
     * @param  string|array  $keys  The key or keys to delete from Redis.
     * @return bool True if the hash was deleted successfully, false otherwise.
     */
    public function destroyHash(string|array $keys)
    {
        if (is_string($keys)) {
            $deleted = (bool) $this->getConnection()->del($keys);
        } elseif (is_array($keys)) {
            $deleted = (bool) $this->getConnection()->del($keys);
        } else {
            $deleted = false;
        }

        return $deleted;
    }

    /**
     * Set a time-to-live on a hash key.
     *
     * @param  string  $keyHash  The key to set the time-to-live.
     * @param  int  $seconds  The number of seconds until the key should expire.
     * @return bool True if the timeout was set successfully, false otherwise.
     */
    public function setExpireByHash(string $keyHash, int $seconds)
    {
        return (bool) $this->getConnection()->expire($keyHash, $seconds);
    }

    /**
     * Get the time-to-live of a hash key.
     *
     * @param  string  $keyHash  The key of the hash to get the time-to-live for.
     * @return int|null The number of seconds until the key will expire, or null if the key does not exist or has no timeout.
     */
    public function getExpireByHash(string $keyHash)
    {
        return $this->getConnection()->ttl($keyHash);
    }

    /**
     * guaranteedScan function scans Redis keys matching the given pattern using the given cursor and retrieves a set number of keys.
     *
     * @param  string  $keyPattern  The pattern to match Redis keys with
     * @param  int  $take  The number of keys to retrieve
     * @param  int  $cursor  The cursor used to continue a scan (default: 0)
     * @param  array  $keyResultRemaining  Array of remaining keys from a previous scan (default: empty array)
     * @return array Returns an array containing the retrieved keys, cursor for the next scan, a boolean indicating if there are more keys available for scanning, and any remaining keys from the scan.
     */
    public function guaranteedScan(string $keyPattern, int $take, int $cursor = 0, $keyResultRemaining = [])
    {
        $cursor = $cursor === 0 ? ((string) $cursor) : $cursor;
        $keys = $keyResultRemaining;

        do {
            [$cursor, $result] = $this->getConnection()->scan($cursor, [
                'match' => $this->getRedisPrefix().$keyPattern,
                'count' => $take,
            ]);

            $keys = array_merge($keys, ($result ?? []));

            if (count($keys) > $take || $cursor == null) {
                break;
            }
        } while ($cursor != '0');

        // creates an array of the first $take keys from the $keys array.
        $keyResult = array_slice($keys, 0, $take);
        // creates an array of the remaining keys after the first $take keys in the $keys array.
        $keyResultRemaining = array_slice($keys, $take);

        return [
            'keys' => $keyResult,
            'cursorNext' => $cursor,
            'isNext' => $cursor != '0' ? true : false,
            'keyResultRemaining' => $keyResultRemaining,
        ];
    }

    /**
     * scanByHash function scans a Redis hash and retrieves its keys and values by calling a callback function for each batch of keys.
     *
     * @param  string  $keyHash  The Redis hash to scan
     * @param  int  $limit  The maximum number of keys to retrieve per batch
     * @param  callable  $callback  A callback function to process each batch of keys
     * @return bool Returns a boolean indicating if the scan was successful or not.
     */
    public function scanByHash(string $keyHash, int $limit, callable $callback)
    {
        $amountOfDataCommit = $this->countByPattern($keyHash);

        //Check the total amount of data that can be retrieved with the hash pattern
        if ($amountOfDataCommit == 0) {
            call_user_func_array($callback, [[], false]);
        } else {
            $cursor = 0;
            $scan = ['cursorNext' => 0, 'isNext' => false, 'keyResultRemaining' => []];

            do {
                $scan = $this->guaranteedScan($keyHash, $limit, $cursor, $scan['keyResultRemaining'] ?? []);

                call_user_func_array($callback, [$scan['keys'], $scan['isNext']]);
                $cursor = $scan['cursorNext'];
            } while ($scan['isNext']);

            // This will ensure that no callback function is missed with remaining data
            // because when the guaranteedScan function notifies that the cursor has been fully iterated,
            // the loop controlled by the while statement will stop and skip any remaining data.
            if ($scan['cursorNext'] === 0 && $scan['isNext'] === false && ! empty($scan['keyResultRemaining'])) {
                call_user_func_array($callback, [$scan['keyResultRemaining'], false]);
            }
        }

        return true;
    }

    /**
     * Run a Redis transaction with the given callback.
     *
     * @param  callable  $callback  The closure to be executed as part of the transaction
     * @return bool|array Returns a boolean indicating if the transaction was successful or not.
     */
    public function transaction(callable $callback)
    {
        return $this->getConnection()->transaction(function ($conTransaction) use ($callback) {
            try {
                $callback($conTransaction);
                $result = $conTransaction->exec();

                if ($result === false) {
                    throw new ErrorTransactionException('Transaction failed to execute');
                }
            } catch (Exception $e) {
                $conTransaction->discard();

                return false;
            }
        });
    }

    /**
     * Removes the Redis prefix from an array of keys.
     *
     * @param  array  $keys  An array of keys with Redis prefix
     * @param  string  $prefix  The Redis key prefix for this model.
     * @return array An array of keys with Redis prefix removed
     */
    public static function removeSlugDatabaseFromRedisKeys(array $keys, string $prefix)
    {
        return array_map(function ($key) use ($prefix) {
            return str_replace($prefix, '', $key);
        }, $keys);
    }
}
