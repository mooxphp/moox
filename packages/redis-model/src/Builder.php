<?php

namespace Moox\RedisModel;

use Illuminate\Redis\Connections\PhpRedisConnection;

class Builder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * @var PhpRedisConnection
     */
    protected $connection;

    /**
     * @var \Moox\RedisModel\Model
     */
    protected $model;

    /**
     * @var \Moox\RedisModel\RedisRepository
     */
    protected $repository;

    /**
     * @var string
     */
    protected $hashPattern = '*';

    /**
     * @var array
     */
    protected $conditionSession = [];

    /**
     * Create a new query builder instance.
     *
     * @return void
     */
    public function __construct(PhpRedisConnection $connection)
    {
        $this->connection = $connection;
        $this->repository = new RedisRepository($connection);
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Moox\RedisModel\RedisRepository|static
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Get the model instance being queried.
     *
     * @return \Moox\RedisModel\Model|static
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set a model instance for the model being queried.
     *
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        $this->setHashPattern($model->getTable().':*');

        return $this;
    }

    /**
     * Set the hash pattern to search for in Redis
     *
     * @param  string  $hashPattern  The hash pattern to search for
     * @return $this
     */
    public function setHashPattern(string $hashPattern)
    {
        $this->hashPattern = $hashPattern;

        return $this;
    }

    /**
     *Get the hash pattern that is being searched for in Redis

     *
     * @return string The hash pattern being searched for
     */
    public function getHashPattern()
    {
        return $this->hashPattern;
    }

    /**
     * Set the session condition for the search
     *
     * @param  array  $condition  An array of conditions to search for
     * @return void
     */
    public function setConditionSession(array $condition)
    {
        $this->conditionSession = $condition;
    }

    /**
     * @return array
     */
    public function getConditionSession()
    {
        return $this->conditionSession;
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string  $value
     * @return $this
     */
    public function where(string|array $column, string|int|null $value = null)
    {
        if ($value && gettype($column) == 'string') {
            $this->setConditionSession(array_merge($this->getConditionSession(), [$column => $value]));
        } elseif (gettype($column) == 'array') {
            $this->setConditionSession(array_merge($this->getConditionSession(), $column));
        }

        $this->setHashPattern($this->compileHashByFields($this->getConditionSession()));

        return $this;
    }

    /**
     * Add a where clause on the primary key to the query.
     *
     * @param  string  $id
     * @return $this
     */
    public function whereKey($id)
    {
        if ($id !== null && $this->model->getKeyType() === 'string') {
            $id = (string) $id;
        }

        return $this->where($this->model->getQualifiedKeyName(), $id);
    }

    /**
     * Add a basic where clause to the query, and return the first result.
     *
     *
     * @param  string  $value
     * @return \Moox\RedisModel\Model|static|null
     */
    public function firstWhere(string|array $column, string|int|null $value = null)
    {
        return $this->where(...func_get_args())->first();
    }

    /**
     * Execute the query and get the first result.
     *
     * @return \Moox\RedisModel\Model|null
     */
    public function first()
    {
        return $this->get()->first();
    }

    /**
     * Execute the destroy data for pattern keys.
     *
     * @return bool
     */
    public function destroy()
    {
        $keys = $this->getRepository()->getHashByPattern($this->getHashPattern());

        return $this->getRepository()->destroyHash($keys);
    }

    /**
     * Execute the fetch properties for keys.
     *
     * @return \Moox\RedisModel\Collection|static[]
     */
    public function get()
    {
        $models = [];

        foreach ($this->getRepository()->fetchHashDataByPattern($this->getHashPattern()) as $hash => $attributes) {
            $models[] = $this->model->newInstance($attributes, true, $hash, true)->syncOriginal();
        }

        return $this->getModel()->newCollection($models);
    }

    /**
     * Counts the number of records that match the hash pattern of the model.
     *
     * @return int The number of records that match the hash pattern.
     */
    public function count()
    {
        return $this->getRepository()->countByPattern($this->getHashPattern());
    }

    /**
     * Create a new Collection instance with the given models.
     *
     *
     * @return \Moox\RedisModel\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Create a new instance of the model being queried.
     *
     * @param  array  $attributes
     * @return \Moox\RedisModel\Model
     */
    public function newModelInstance($attributes = [])
    {
        return $this->model->newInstance($attributes);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id  The primary key value of the model to find.
     * @return \Moox\RedisModel\Model|null The found model or null if not found.
     */
    public function find($id)
    {
        // Retrieves the first model that matches the specified primary key.
        $model = $this->whereKey($id)->first();

        // If the model is found, sync its original state and return a clone of it.
        if ($model instanceof Model) {
            $model->syncOriginal();

            return clone $model;
        }

        return null;
    }

    /**
     * Save a new model and return the instance.
     *
     * @param  array  $attributes  - The attributes to create the model with.
     * @return \Moox\RedisModel\Model|$this - The newly created model instance.
     */
    public function create(array $attributes = [])
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Save a new model and return the instance. Allow mass-assignment.
     *
     * @param  array  $attributes  The attributes to be saved.
     * @return \Moox\RedisModel\Model|$this The created model instance.
     */
    public function forceCreate(array $attributes)
    {
        return tap($this->newModelInstance($attributes), function ($instance) {
            $instance->setPrioritizeForceSave();
            $instance->save();
        });
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count  The number of models to retrieve per chunk
     * @param  callable|null  $callback  Optional callback function to be executed on each chunk
     * @return \Moox\RedisModel\Collection A collection of the retrieved models, chunked
     */
    public function chunk($count, ?callable $callback = null)
    {
        $resultData = $this->newCollection([]);

        // Scan for the models in the Redis database, and execute the provided callback function (if any) on each chunk
        $this->getRepository()
            ->scanByHash(
                $this->getHashPattern(),
                $count,
                function ($keys) use ($callback, $resultData) {
                    $modelsChunk = [];

                    // Fetch the attributes of the models in the current chunk, and create new model instances with
                    // these attributes
                    foreach ($this->getRepository()->fetchProperByListHash($keys) as $hash => $attributes) {
                        $modelsChunk[] = $this->model->newInstance($attributes, true, $hash, true)->syncOriginal();
                    }

                    $resultData->push($modelsChunk);

                    // Execute the provided callback function (if any) on the current chunk of models
                    $callback == null ?: $callback($modelsChunk);
                }
            );

        return $resultData;
    }

    /**
     * Checks if a hash record exists in Redis based on the given model attributes.
     *
     * @param  array  $attributes  The attributes to check in the hash record.
     * @return bool Returns true if a hash record exists in Redis for the given attributes, false otherwise.
     */
    public function isExists(array $attributes)
    {
        return empty($this->getRepository()->getHashByPattern($this->compileHashByFields($attributes))) ? false : true;
    }

    /**
     * Compile a hash key by fields of the given attributes array.
     *
     * @param  array  $attributes  The array of attributes.
     * @return string The compiled hash key.
     */
    public function compileHashByFields(array $attributes)
    {
        $listKey = array_merge([$this->model->getKeyName()], $this->model->getSubKeys());
        $stringKey = '';

        foreach ($listKey as $key) {
            $attributeValue = $attributes[$key] ?? '*';
            $stringKey .= $key.':'.($attributeValue === '*' ? '*' : $this->model->castAttributeBeforeSave($key, $attributeValue)).':';
        }

        return $this->model->getTable().':'.rtrim($stringKey, ':');
    }
}
