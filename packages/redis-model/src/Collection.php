<?php

namespace Moox\RedisModel;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Find a model in the collection by key.
     *
     * @template TFindDefault
     *
     * @param  mixed  $key
     * @param  TFindDefault  $default
     * @return static<TKey, TModel>|TModel|TFindDefault
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getKey();
        }

        if ($key instanceof Arrayable) {
            $key = $key->toArray();
        }

        if (is_array($key)) {
            if ($this->isEmpty()) {
                return new static;
            }

            return $this->whereIn($this->first()->getKeyName(), $key);
        }

        return Arr::first($this->items, fn ($model) => $model->getKey() == $key, $default);
    }

    /**
     * Determine if a key exists in the collection.
     *
     * @param  (callable(TModel, TKey): bool)|TModel|string|int  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() > 1 || $this->useAsCallable($key)) {
            return parent::contains(...func_get_args());
        }

        if ($key instanceof Model) {
            return parent::contains(fn ($model) => $model->is($key));
        }

        return parent::contains(fn ($model) => $model->getKey() == $key);
    }

    /**
     * Get the array of primary keys.
     *
     * @return array<int, array-key>
     */
    public function modelKeys()
    {
        return array_map(fn ($model) => $model->getKey(), $this->items);
    }

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TModel, TKey): TMapValue  $callback
     * @return \Illuminate\Support\Collection<TKey, TMapValue>|static<TKey, TMapValue>
     */
    public function map(callable $callback)
    {
        $result = parent::map($callback);

        return $result->contains(fn ($item) => ! $item instanceof Model) ? $result->toBase() : $result;
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key / value pair.
     *
     * @template TMapWithKeysKey of array-key
     * @template TMapWithKeysValue
     *
     * @param  callable(TModel, TKey): array<TMapWithKeysKey, TMapWithKeysValue>  $callback
     * @return \Illuminate\Support\Collection<TMapWithKeysKey, TMapWithKeysValue>|static<TMapWithKeysKey, TMapWithKeysValue>
     */
    public function mapWithKeys(callable $callback)
    {
        $result = parent::mapWithKeys($callback);

        return $result->contains(fn ($item) => ! $item instanceof Model) ? $result->toBase() : $result;
    }

    /**
     * Diff the collection with the given items.
     *
     * @param  iterable<array-key, TModel>  $items
     * @return static
     */
    public function diff($items)
    {
        $diff = new static;

        $dictionary = $this->getDictionary($items);

        foreach ($this->items as $item) {
            if (! isset($dictionary[$this->getDictionaryKey($item->getKey())])) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Return only unique items from the collection.
     *
     * @param  (callable(TModel, TKey): mixed)|string|null  $key
     * @param  bool  $strict
     * @return static<int, TModel>
     */
    public function unique($key = null, $strict = false)
    {
        if ($key === null) {
            return parent::unique($key, $strict);
        }

        return new static(array_values($this->getDictionary()));
    }

    /**
     * Get a dictionary key attribute - casting it to a string if necessary.
     *
     * @param  mixed  $attribute
     * @return mixed
     *
     * @throws \Doctrine\Instantiator\Exception\InvalidArgumentException
     */
    protected function getDictionaryKey($attribute)
    {
        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }

            if ($attribute instanceof UnitEnum) {
                return $attribute instanceof BackedEnum ? $attribute->value : $attribute->name;
            }

            throw new InvalidArgumentException('Model attribute value is an object but does not have a __toString method.');
        }

        return $attribute;
    }

    /**
     * Returns only the models from the collection with the specified keys.
     *
     * @param  array<array-key, mixed>|null  $keys
     * @return static<int, TModel>
     */
    public function only($keys)
    {
        if ($keys === null) {
            return new static($this->items);
        }

        $dictionary = Arr::only($this->getDictionary(), array_map($this->getDictionaryKey(...), (array) $keys));

        return new static(array_values($dictionary));
    }

    /**
     * Returns all models in the collection except the models with specified keys.
     *
     * @param  array<array-key, mixed>|null  $keys
     * @return static<int, TModel>
     */
    public function except($keys)
    {
        $dictionary = Arr::except($this->getDictionary(), array_map($this->getDictionaryKey(...), (array) $keys));

        return new static(array_values($dictionary));
    }

    /**
     * Append an attribute across the entire collection.
     *
     * @param  array<array-key, string>|string  $attributes
     * @return $this
     */
    public function append($attributes)
    {
        return $this->each->append($attributes);
    }

    /**
     * Get a dictionary keyed by primary keys.
     *
     * @param  iterable<array-key, TModel>|null  $items
     * @return array<array-key, TModel>
     */
    public function getDictionary($items = null)
    {
        $items = $items === null ? $this->items : $items;

        $dictionary = [];

        foreach ($items as $value) {
            $dictionary[$this->getDictionaryKey($value->getKey())] = $value;
        }

        return $dictionary;
    }

    /**
     * The following methods are intercepted to always return base collections.
     */

    /**
     * Count the number of items in the collection by a field or using a callback.
     *
     * @param  (callable(TModel, TKey): array-key)|string|null  $countBy
     * @return \Illuminate\Support\Collection<array-key, int>
     */
    public function countBy($countBy = null)
    {
        return $this->toBase()->countBy($countBy);
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function collapse()
    {
        return $this->toBase()->collapse();
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param  int  $depth
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function flatten($depth = INF)
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * Flip the items in the collection.
     *
     * @return \Illuminate\Support\Collection<TModel, TKey>
     */
    public function flip()
    {
        return $this->toBase()->flip();
    }

    /**
     * Get the keys of the collection items.
     *
     * @return \Illuminate\Support\Collection<int, TKey>
     */
    public function keys()
    {
        return $this->toBase()->keys();
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @template TPadValue
     *
     * @param  int  $size
     * @param  TPadValue  $value
     * @return \Illuminate\Support\Collection<int, TModel|TPadValue>
     */
    public function pad($size, $value)
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * Get an array with the values of a given key.
     *
     * @param  string|array<array-key, string>  $value
     * @param  string|null  $key
     * @return \Illuminate\Support\Collection<array-key, mixed>
     */
    public function pluck($value, $key = null)
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * @template TZipValue
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<array-key, TZipValue>|iterable<array-key, TZipValue>  ...$items
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, TModel|TZipValue>>
     */
    public function zip($items)
    {
        return $this->toBase()->zip(...func_get_args());
    }

    /**
     * Get the comparison function to detect duplicates.
     *
     * @param  bool  $strict
     * @return callable(TModel, TModel): bool
     */
    protected function duplicateComparator($strict)
    {
        return fn ($a, $b) => $a->is($b);
    }
}
