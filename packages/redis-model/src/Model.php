<?php

namespace Moox\RedisModel;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\CanBeEscapedWhenCastToString;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Concerns\GuardsAttributes;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis as RedisFacade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use JsonSerializable;
use Moox\RedisModel\Exceptions\ErrorConnectToRedisException;
use Moox\RedisModel\Exceptions\KeyExistException;
use Moox\RedisModel\Exceptions\MassAssignmentException;
use Moox\RedisModel\Exceptions\MissingAttributeException;
use Moox\RedisModel\Exceptions\RedisModelException;
use Redis;
use ReflectionClass;

abstract class Model implements Arrayable, ArrayAccess, CanBeEscapedWhenCastToString, Jsonable, JsonSerializable
{
    use ForwardsCalls,
        GuardsAttributes,
        HasAttributes,
        HasTimestamps,
        HidesAttributes;

    /**
     * The name of the "created at" column.
     *
     * @var string|null
     */
    const CREATED_AT = 'created_at';

    /**
     * The name of the "updated at" column.
     *
     * @var string|null
     */
    const UPDATED_AT = 'updated_at';

    /**
     * The name index of redis client prefix.
     *
     * @var int|null
     */
    const REDIS_CLIENT_PREFIX = 2;

    /**
     * The model's table.
     *
     * @var array
     */
    protected $table = null;

    /**
     * The model's prefixTable.
     *
     * @var array
     */
    protected $prefixTable = null;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The model's sub keys for the model.
     *
     * @var array
     */
    protected $subKeys = [];

    /**
     * The connection name
     *
     * @var string|null
     */
    protected $connectionName = null;

    /**
     * The connection resolver instance.
     *
     * @var Redis|null
     */
    private $connection = null;

    /**
     * Indicates when generating but key exists.
     *
     * @var bool
     */
    protected $preventCreateForce = true;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Allow undeclared fillable to appear in the model.
     *
     * @var bool
     */
    protected $flexibleFill = true;

    /**
     * The final key after the model key has been fully compiled.
     *
     * @var string
     */
    public $redisKey = '';

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }

    /**
     * Set whether IDs are incrementing.
     *
     * @param  bool  $value
     * @return $this
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;

        return $this;
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return $this
     */
    public function setKey($value)
    {
        $this->setAttribute($this->getKeyName(), $value);

        return $this;
    }

    /**
     * @return array
     */
    public function getSubKeys()
    {
        return $this->subKeys;
    }

    /**
     * set sub keys.
     *
     * @return $this
     */
    public function setSubKeys(array $subKeys)
    {
        $this->subKeys = $subKeys;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPreventCreateForce()
    {
        return $this->preventCreateForce;
    }

    /**
     * @return $this
     */
    public function initialInfoTable()
    {
        $this->setPrefixTable();
        $this->setTable();

        return $this;
    }

    /**
     * Get the table associated with the model.
     *
     * @return mixed
     */
    public function setPrefixTable()
    {
        $this->prefixTable = $this->prefixTable;
    }

    /**
     * Get the prefix table associated with the model.
     *
     * @return mixed
     */
    public function getPrefixTable()
    {
        return $this->prefixTable;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * set the table associated with the model.
     *
     * @return $this
     */
    public function setTable($table = null)
    {
        $defaultTableName = Str::snake(Str::pluralStudly(class_basename($this)));

        $this->table = $this->getPrefixTable().($table ?? $this->table ?? $defaultTableName);

        return $this;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
     *
     * @param  string  $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;

        return $this;
    }

    /**
     * Get the data type for the primary key.
     *
     * @return string
     */
    public function getKeyType()
    {
        return $this->keyType;
    }

    /**
     * Set the data type for the primary key.
     *
     * @param  string  $type
     * @return $this
     */
    public function setKeyType($type)
    {
        $this->keyType = $type;

        return $this;
    }

    /**
     * Get connection name
     *
     * @return string
     */
    public function getConnectionName()
    {
        $defaultConnectionName = config('redis-model.redis_model_options.database_default', 'default');

        return $this->connectionName = $this->connectionName ?? $defaultConnectionName;
    }

    public function setPrefixConnector(): void
    {
        $this->getConnection()->client()->setOption(self::REDIS_CLIENT_PREFIX, $this->getRedisPrefix());
    }

    /**
     * @return string
     */
    public function getRedisPrefix()
    {
        $defaultPrefix = config('database.redis.options.prefix', 'redis_model_');

        return config('redis-model.redis_model_options.prefix', $defaultPrefix);
    }

    /**
     * Set connection
     *
     * @param  string|null  $nameConnect
     * @return $this
     */
    public function setConnection(string $connectionName)
    {
        try {
            $this->connection = RedisFacade::connection($connectionName);
            $this->setPrefixConnector();
        } catch (Exception $e) {
            throw new ErrorConnectToRedisException($e->getMessage());
        }

        return $this;
    }

    /**
     * Join a Redis transaction with the current connection.
     *
     * @param  Redis  $connection
     * @return $this
     */
    public function joinTransaction(Redis $clientTransaction)
    {
        tap($this->connection, function ($connect) use ($clientTransaction) {
            $reflectionClass = new ReflectionClass(\get_class($connect));
            $client = $reflectionClass->getProperty('client');
            $client->setAccessible(true);
            $client->setValue($connect, $clientTransaction);
            $this->connection = $connect;
        });

        return $this;
    }

    /**
     * Get connection
     *
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @return self
     *
     * @throws \Moox\RedisModel\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        $fillable = $this->fillableFromArray($attributes);

        foreach ($fillable as $key => $value) {
            // The developers may choose to place some attributes in the "fillable" array
            // which means only those attributes may be set through mass assignment to
            // the model, and all others will just get ignored for security reasons.
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {

                throw new MassAssignmentException(sprintf(
                    'Add [%s] to fillable property to allow mass assignment on [%s].',
                    $key, get_class($this)
                ));
            }
        }

        if (count($attributes) !== count($fillable)) {
            $keys = array_diff(array_keys($attributes), array_keys($fillable));
            if ($this->flexibleFill) {
                foreach ($keys as $key) {
                    $this->setAttribute($key, $attributes[$key]);
                }
            } else {
                throw new MassAssignmentException(sprintf(
                    'Add fillable property [%s] to allow mass assignment on [%s].',
                    implode(', ', $keys),
                    get_class($this)
                ));
            }
        }

        return $this;
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     * @return bool
     */
    public function save()
    {
        $this->mergeAttributesFromCachedCasts();
        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.

        $query = $this->newQuery();

        if ($this->exists) {
            $saved = $this->isDirty() ?
            $this->performUpdate($query) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
            $this->finishSave();
        }

        return $saved;
    }

    /**
     * Perform any actions that are necessary after the model is saved.
     *
     * @param  array  $options
     * @return self
     */
    protected function finishSave()
    {
        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     *
     * @return bool
     */
    protected function performUpdate(Builder $build)
    {
        // First we need to create a fresh query instance and touch the creation and
        // update timestamp on the model which are maintained by us for developer
        // convenience. Then we will just continue saving the model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        // Once we have run the update operation, we will fire the "updated" event for
        // this model instance. This will allow developers to hook into these after
        // models are updated, giving them a chance to do any special processing.
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            $attributes = $this->getAttributesForInsert();
            if ($this->isValidationKeyAndSubKeys($attributes)) {
                $attributes = collect($attributes)->map(function ($item, $key) {
                    return (string) $this->castAttributeBeforeSave($key, $item);
                })->toArray();

                $keyOrigin = $build->compileHashByFields($this->parseAttributeKeyBooleanToInt(($this->getOriginal())));
                $keyNew = $build->compileHashByFields($this->parseAttributeKeyBooleanToInt($attributes));
                $build->getRepository()->updateRedisHashes($keyOrigin, $attributes, $keyNew);

                $this->exists = true;
                $this->redisKey = $keyNew;

                return true;
            } else {
                throw new RedisModelException('Primary key and sub key values are required');
            }
        }

        return false;
    }

    /**
     * Casts and prepares an attribute value before saving it to the database.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed The
     */
    public function castAttributeBeforeSave($key, $value)
    {
        // Cast the attribute if necessary
        $value = $this->hasCast($key) ? $this->castAttribute($key, $value) : $value;

        // If the attribute is a Carbon instance, format it using the model's date format
        if ($value instanceof Carbon) {
            $value = $value->format($this->getDateFormat());
        }

        // If the attribute is an array, encode it to JSON
        if (is_array($value)) {
            $value = json_encode($value);
        }

        // If the attribute is a boolean, cast it to an integer
        if (is_bool($value)) {
            $value = (int) filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // If the attribute is enum castable, extract its value
        if ($this->isEnumCastable($key)) {
            $value = $value->value;
        }

        // Return the transformed and casted attribute value
        return $value;
    }

    /**
     * Perform a model insert operation.
     *
     * @return bool
     */
    protected function performInsert(Builder $build)
    {
        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        if ($this->getIncrementing() && $this->getKeyType() && $this->getKey() == null) {
            $this->setKey(Str::uuid());
        }

        if (! $this->isPrioritizeForceSave()) {
            if ($this->getPreventCreateForce() && $this->isKeyExist()) {
                throw new KeyExistException(
                    'Key '.$this->getKeyName().' '.$this->{$this->getKeyName()}.' already exists.'
                );
            }
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->getAttributesForInsert();

        if (empty($attributes)) {
            return true;
        }

        if ($this->isValidationKeyAndSubKeys($attributes)) {
            $attributes = collect($attributes)->map(function ($item, $key) {
                return (string) $this->castAttributeBeforeSave($key, $item);
            })->toArray();

            $keyInsert = $build->compileHashByFields($this->parseAttributeKeyBooleanToInt($attributes));
            $build->getRepository()->insertRedisHashes($keyInsert, $attributes);
        } else {
            throw new RedisModelException('Primary key and sub key values are required');
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;
        $this->redisKey = $keyInsert;

        return true;
    }

    /**
     * Deletes the current model from Redis if the primary key and sub-keys are valid.
     * If the delete operation is successful, it returns true. Otherwise, it returns false.
     *
     * @return bool Returns true if the deletion is successful; otherwise, false.
     */
    public function performDeleteOnModel()
    {
        if ($this->isValidationKeyAndSubKeys($this->getOriginal())) {
            $build = $this->query();
            $keyRemove = $build->compileHashByFields($this->getOriginal());
            $build->getRepository()->destroyHash($keyRemove);
        } else {
            return false;
        }

        $this->exists = false;

        return true;
    }

    /**
     * Inserts multiple data into Redis hashes.
     *
     * @param  array  $dataInsert  An array of data to insert into Redis hashes.
     * @param  Redis  $hasTransaction  a redis client.
     * @return mixed Returns the result of inserting multiple Redis hashes, or false if the data is invalid.
     */
    public static function insert(array $dataInsert, ?Redis $hasTransaction = null)
    {
        $inserts = [];
        $build = static::query();
        $model = $build->getModel();

        if ($hasTransaction) {
            $model->joinTransaction($hasTransaction);
        }

        foreach ($dataInsert as $attributes) {
            if ($model->getIncrementing() && $model->getKeyType() && ! isset($attributes[$model->getKeyName()])) {
                $attributes[$model->getKeyName()] = Str::uuid();
            }

            if ($model->isValidationKeyAndSubKeys($attributes)) {
                $key = $build->compileHashByFields($attributes);

                // If the model uses timestamps, update them in the attributes
                if ($model->usesTimestamps()) {
                    $model->updateTimestamps();
                    $attributes = array_merge($attributes, $model->getAttributes());
                }

                $inserts[$key] = collect($attributes)->map(function ($item, $key) use ($model) {
                    return (string) $model->castAttributeBeforeSave($key, $item);
                })->toArray();
            } else {
                return false;
            }
        }

        return $build->getRepository()->insertMultipleRedisHashes($inserts);
    }

    /**
     * Update the model in the database.
     *
     *
     * @return bool
     */
    public function update(array $attributes = [])
    {
        if (! $this->exists) {
            return false;
        }

        return $this->fill($attributes)->save();
    }

    /**
     * Set the number of seconds expire key model
     *
     *
     * @return bool
     */
    public function setExpire(int|Carbon $seconds)
    {
        if (! $this->exists) {
            return false;
        }

        if ($seconds instanceof Carbon) {
            $seconds = now()->diffInSeconds($seconds);
        }

        if ($this->isValidationKeyAndSubKeys($this->getOriginal())) {
            $build = $this->query();
            $key = $build->compileHashByFields($this->getOriginal());

            return $build->getRepository()->setExpireByHash($key, $seconds);
        } else {
            return false;
        }
    }

    /**
     * Get the number of seconds expire key model
     *
     * @return bool
     */
    public function getExpire()
    {
        if (! $this->exists) {
            return false;
        }

        if ($this->isValidationKeyAndSubKeys($this->getOriginal())) {
            $build = $this->query();
            $key = $build->compileHashByFields($this->getOriginal());

            return $build->getRepository()->getExpireByHash($key);
        } else {
            return false;
        }
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete()
    {
        $this->mergeAttributesFromCachedCasts();

        if ($this->getKeyName() === null) {
            throw new LogicException('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        if (! $this->exists) {
            return;
        }

        $this->performDeleteOnModel();

        return true;
    }

    /**
     * Get all of the models from the database.
     *
     * @return \Moox\RedisModel\Collection<int, static>
     */
    public static function all()
    {
        return static::query()->get();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @return \Moox\RedisModel\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Begin querying the model.
     *
     * @return \Moox\RedisModel\Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Run a transaction with the given callback.
     *
     *
     * @return mixed The result of the callback
     */
    public static function transaction(callable $callback)
    {
        $build = static::query();

        return $build->getRepository()->transaction($callback);
    }

    /**
     * Get a new query builder for the model's table.
     *
     * @return \Moox\RedisModel\Builder
     */
    public function newQuery()
    {
        return $this->newBuilder($this->getConnection())->setModel($this);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Moox\RedisModel  $query
     * @return Moox\RedisModel\Builder
     */
    public function newBuilder($connection)
    {
        return new Builder($connection);
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @param  bool  $isCastAttribute
     * @return static
     */
    public function newInstance($attributes = [], $exists = false, ?string $redisKey = null, $isCastAttribute = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = new static;

        $model->exists = $exists;

        $model->redisKey = $redisKey;

        $this->setDateFormat('Y-m-d\\TH:i:sP');

        $model->setTable($this->getTable());

        $model->mergeCasts($this->casts);

        if ($isCastAttribute) {
            $castAttributes = collect($attributes)->mapWithKeys(function ($value, $key) use ($model) {
                return [$key => $model->transformModelValue($key, $value)];
            })->all();

            $model->fill((array) $castAttributes);
        } else {
            $model->fill((array) $attributes);
        }

        return $model;
    }

    /**
     * Create a new Eloquent model instance.
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setDateFormat('Y-m-d\\TH:i:sP');
        $this->initialInfoTable();
        $this->setConnection($this->getConnectionName());
        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo($this->query(), $method, $parameters);
    }

    /**
     * Handle dynamic static method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->escapeWhenCastingToString
        ? e($this->toJson())
        : $this->toJson();
    }

    /**
     * Indicate that the object's string representation should be escaped when __toString is invoked.
     *
     * @param  bool  $escape
     * @return $this
     */
    public function escapeWhenCastingToString($escape = true)
    {
        $this->escapeWhenCastingToString = $escape;

        return $this;
    }

    /**
     * Prepare the object for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $this->mergeAttributesFromCachedCasts();

        $this->classCastCache = [];
        $this->attributeCastCache = [];

        return array_keys(get_object_vars($this));
    }

    /**
     * Convert the model instance to an array.
     */
    public function toArray(): array
    {
        return $this->attributesToArray();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param  int  $options
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        return $json;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
     *
     * @return void
     */
    public function __wakeup() {}

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     */
    public function offsetExists($offset): bool
    {
        try {
            return $this->getAttribute($offset) === null;
        } catch (MissingAttributeException) {
            return false;
        }
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Determine if the given key is a relationship method on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function isRelation($key)
    {
        return false;
    }

    /**
     * Determine if the given relation is loaded.
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return false;
    }

    /**
     * Determine if two models are not the same.
     *
     * @param  \Moox\RedisModel\Model|null  $model
     * @return bool
     */
    public function isNot($model)
    {
        return ! $this->is($model);
    }

    /**
     * Get the table qualified key name.
     *
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->qualifyColumn($this->getKeyName());
    }

    /**
     * Qualify the given column name by the model's table.
     *
     * @param  string  $column
     * @return string
     */
    public function qualifyColumn($column)
    {
        if (str_contains($column, '.')) {
            return $column;
        }

        return $column;
    }

    /**
     * Qualify the given columns with the model's table.
     *
     * @param  array  $columns
     * @return array
     */
    public function qualifyColumns($columns)
    {
        return collect($columns)->map(function ($column) {
            return $this->qualifyColumn($column);
        })->all();
    }

    /**
     * Set flag force insert of model
     *
     * @return self
     */
    public function setPrioritizeForceSave()
    {
        $this->prioritizeForceSave = true;

        return $this;
    }

    /**
     * Get flag force insert of model
     *
     * @return bool
     */
    protected function isPrioritizeForceSave()
    {
        if (isset($this->attributes['prioritizeForceSave'])) {
            unset($this->attributes['prioritizeForceSave']);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function isKeyExist()
    {
        return $this->query()->isExists($this->getAttributesForInsert());
    }

    /**
     * @return bool
     */
    protected function isValidationKeyAndSubKeys($attributes)
    {
        $listKey = array_merge([$this->getKeyName()], $this->getSubKeys());

        foreach ($listKey as $key) {
            if (! isset($attributes[$key]) ||
                (isset($this->getCasts()[$key]) && $this->getCasts()[$key] != 'boolean' && empty($attributes[$key]))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array  $value
     * @return int
     */
    private function parseAttributeKeyBooleanToInt($attributes)
    {
        foreach ($attributes as $key => $value) {
            if (isset($this->getCasts()[$key]) && $this->getCasts()[$key] == 'boolean') {
                $attributes[$key] = (int) filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $attributes;
    }
}
