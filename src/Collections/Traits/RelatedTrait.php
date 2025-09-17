<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections\Traits;

use InvalidArgumentException;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;


/**
 * @property bool $isProxyAllowed
 * @property bool $isPaginate
 * @property bool $isBuilder
 * @property mixed $builder
 */
trait RelatedTrait{

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->__unset($offset);
    }

    /**
     * Determine if the collection has a given key.
     *
     * @param  mixed  $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Dynamically access collection items.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return  $this->items[$key] ?? null;
    }

    /**
     * Dynamically set an item in the collection.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Check if an item exists in the collection.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Remove an item from items collection.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return ! $this->isEmpty();
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        if($this->isProxyAllowed){
            return true;
        }
        return $this->count() === 0 ? true : false;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        if($this->isProxyAllowed){
            return 0;
        }
        return  $this->isArray() ? count($this->items) : 0;
    }

    /**
     * Get all items as an array.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }
    
    /**
     * Check if items is an array
     * 
     * @return bool
     */ 
    private function isArray()
    {
        return (is_array($this->items) || $this->items instanceof \Countable);
    }

    /**
     * Check if all values of array is same
     * 
     * @return bool
     */
    public function isSame()
    {
        return Str::arraySame($this->items);
    }

    /**
     * Check if array has duplicate value
     *
     * @param bool $strict
     * @return bool
     */
    public function isDuplicate($strict = false)
    {
        return Str::arrayDuplicate($this->items, $strict);
    }

    /**
     * Reverse the collection order.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, true));
    }

    /**
     * Pad the collection to the specified length with a value.
     *
     * @param  int $size
     * @param  mixed $value
     * @return static
     */
    public function pad(int $size, $value)
    {
        return new static(array_pad($this->items, $size, $value));
    }

    /**
     * Combine the collection's values with the provided keys.
     *
     * @param  array $keys
     * @return static
     */
    public function combine(array $keys)
    {
        // If $keys is longer than $this->items, trim it
        if (count($keys) > count($this->items)) {
            $keys = array_slice($keys, 0, count($this->items));
        }

        // If $keys is shorter than $this->items, pad it with null values
        if (count($keys) < count($this->items)) {
            $keys = array_pad($keys, count($this->items), null);
        }

        return new static(array_combine($keys, $this->items));
    }

    /**
     * Collapse a multi-dimensional collection into a single level.
     *
     * @return static
     */
    public function collapse()
    {
        $results = [];

        foreach ($this->items as $values) {
            if (is_array($values) || $values instanceof self) {
                $results = array_merge($results, $values instanceof self ? $values->all() : $values);
            }
        }

        return new static($results);
    }

    /**
     * Flatten a multi-dimensional collection into a single level.
     *
     * @return static
     */
    public function flatten()
    {
        return new static(Str::flatten($this->items));
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * @param  array ...$arrays
     * @return static
     */
    public function zip(array ...$arrays)
    {
        $zipped = [];

        foreach ($this->items as $index => $value) {
            $row = [$value];
            foreach ($arrays as $array) {
                $row[] = $array[$index] ?? null;
            }
            $zipped[] = $row;
        }

        return new static($zipped);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  array $items
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $items));
    }

    /**
     * Needed key from items
     * @param array|string|null  $keys of input
     * 
     * @return static
     */
    public function only(...$keys)
    {
        $keys = Str::flatten($keys);

        $array = [];

        foreach($keys as $key){
            if(in_array($key, array_keys($this->items))){
                $array[$key] = $this->items[$key];
            }
        }

        return new static($array);
    }

    /**
     * Remove key from items
     * @param array|string|null  $keys of input
     * 
     * @return static
     */
    public function except(...$keys)
    {
        $keys = Str::flatten($keys);

        foreach($keys as $key){
            if(in_array($key, array_keys($this->items))){
                unset($this->items[$key]);
            }
        }

        return new static($this->items);
    }

    /**
     * Chunk the collection into arrays with a specified size.
     *
     * @param  int $size
     * @return static
     */
    public function chunk(int $size)
    {
        if ($size <= 0) {
            $size = 10;
        }

        return new static(array_chunk($this->items, $size));
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the values of the collection items.
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Filter the collection using a callback.
     *
     * @param  callable $callback
     * @return static
     */
    public function filter(callable $callback)
    {
        return new static(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Remove items from the collection that pass a given truth test.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function reject($callback = null)
    {
        // If no callback is provided, remove "truthy" values
        if (is_null($callback)) {
            $callback = fn($item) => (bool) $item;
        }

        // array_filter keeps items where callback returns false
        $results = array_filter($this->items, function ($item, $key) use ($callback) {
            return ! $callback($item, $key);
        }, ARRAY_FILTER_USE_BOTH);

        return new static($results);
    }

    /**
     * Filter the collection by the given key, operator, and value.
     *
     * @param  string|callable $key
     * @param  string|null $operator
     * @param  mixed $value
     * @return static
     */
    public function where($key, $operator = null, $value = null)
    {
        // If only 2 values are passed to the method, then operator is default by equals sign
        // Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        $results = array_filter($this->items, function ($item) use ($key, $operator, $value) {
            return $this->evaluateWhere($item, $key, $operator, $value);
        });

        return new static($results);
    }

    /**
     * Filter items where the given key is in the provided values.
     *
     * @param  string $key
     * @param  array $values
     * @return static
     */
    public function whereIn(string $key, array $values)
    {
        return $this->filter(function ($item) use ($key, $values) {
            return isset($item[$key]) && in_array($item[$key], $values, true);
        });
    }

    /**
     * Filter items where the given key is not in the provided values.
     *
     * @param  string $key
     * @param  array $values
     * @return static
     */
    public function whereNotIn(string $key, array $values)
    {
        return $this->filter(function ($item) use ($key, $values) {
            return !isset($item[$key]) || !in_array($item[$key], $values, true);
        });
    }

    /**
     * Filter items where the given key is null.
     *
     * @param  string $key
     * @return static
     */
    public function whereNull(string $key)
    {
        return $this->filter(function ($item) use ($key) {
            return !isset($item[$key]) || is_null($item[$key]);
        });
    }

    /**
     * Filter items where the given key is not null.
     *
     * @param  string $key
     * @return static
     */
    public function whereNotNull(string $key)
    {
        return $this->filter(function ($item) use ($key) {
            return isset($item[$key]) && !is_null($item[$key]);
        });
    }

    /**
     * Get the first element from the collection.
     *
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first($callback = null, $default = null)
    {
        if (is_null($callback)) {
            $first = Str::head($this->items);

            return $first !== false ? $first : $default;
        }

        // With callback: return the first item matching the callback
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Get the first element matching the given key/value pair(s).
     *
     * @param string|callable $key
     * @param mixed $operator
     * @param mixed $value
     * @return mixed|null
     */
    public function firstWhere($key, $operator = null, $value = null)
    {
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        return $this->where($key, $operator, $value)->first();
    }

    /**
     * Get the last item in the collection.
     *
     * @return mixed
     */
    public function last()
    {
        return Str::last($this->items);
    }

    /**
     * Determine if the collection contains a given value.
     *
     * @param  mixed $value
     * @param  bool $strict
     * @return bool
     */
    public function contains($value, bool $strict = false)
    {
        foreach ($this->items as $item) {
            // If the value is an array, check for matching key-value pairs
            if (is_array($value) && !empty($value)) {
                $matches = true;
                foreach ($value as $key => $val) {
                    // If the value is a string, perform a case-insensitive comparison
                    if (!isset($item[$key])) {
                        $matches = false;
                        break;
                    }
    
                    $itemValue = $item[$key];
    
                    // Case-insensitive comparison for string values
                    if (is_string($itemValue) && is_string($val)) {
                        if (strcasecmp($itemValue, $val) !== 0) {
                            $matches = false;
                            break;
                        }
                    }
                    // Standard comparison for non-string values (still respects $strict)
                    elseif ($strict ? $itemValue !== $val : $itemValue != $val) {
                        $matches = false;
                        break;
                    }
                }
    
                if ($matches) {
                    return true; // If all key-value pairs match, return true
                }
            } else {
                // For non-array values, check if the value is present in the collection
                if (is_string($value)) {
                    // Case-insensitive string check
                    $found = false;
                    foreach ($this->items as $item) {
                        if (is_string($item) && strcasecmp($item, $value) === 0) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        return true;
                    }
                } else {
                    // For non-string values, check with in_array
                    if (in_array($value, $this->items, $strict)) {
                        return true;
                    }
                }
            }
        }
    
        return false; // Return false if no match was found
    }

    /**
     * Determine if an item is not contained in the collection.
     *
     * @param  mixed  $key
     * @param  mixed  $operator
     * @param  mixed  $value
     * @return bool
     */
    public function doesntContain($key, $operator = null, $value = null)
    {
        // If $value is not provided, we assume we are checking for a simple key-value pair
        if ($value === null && $operator !== null) {
            return ! $this->contains([$key => $operator]);
        }

        // Otherwise, handle cases where operator and value are explicitly provided
        if ($value !== null) {
            return ! $this->contains([$key => $value]);
        }

        return true; // Return true if no valid match is found
    }

    /**
     * Determine if all items pass the given truth test.
     *
     * @param  callable $callback
     * @return bool
     */
    public function every(callable $callback): bool
    {
        foreach ($this->items as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine if at least one item passes the given truth test.
     *
     * @param  callable $callback
     * @return bool
     */
    public function some(callable $callback): bool
    {
        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Select specific keys from the collection.
     *
     * - Supports arrays, ArrayAccess objects, and plain objects (public properties).
     * - Preserves original item keys.
     * - If no keys are provided, returns the collection unchanged.
     *
     * @param  string|array $keys
     * @return static
     */
    public function select(...$keys)
    {
        $keys = Str::flatten($keys);

        // If no keys provided, return as-is
        if (empty($keys)) {
            return new static($this->items);
        }

        $selected = [];

        foreach ($this->items as $outerKey => $item) {
            $result = [];

            foreach ($keys as $key) {
                if (is_array($item)) {
                    if (array_key_exists($key, $item)) {
                        $result[$key] = $item[$key];
                    }
                } elseif ($item instanceof \ArrayAccess) {
                    $exists = method_exists($item, 'offsetExists') ? $item->offsetExists($key) : isset($item[$key]);
                    if ($exists) {
                        $result[$key] = $item[$key];
                    }
                } elseif (is_object($item) && isset($item->{$key})) {
                    $result[$key] = $item->{$key};
                }
            }

            $selected[$outerKey] = $result;
        }

        return new static($selected);
    }

    /**
     * Map the collection items using a callback.
     *
     * @param  callable $callback
     * @return static
     */
    public function map(callable $callback)
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Map the collection into a new collection keyed by the given callback.
     *
     * The callback should return an associative array containing a single
     * key-value pair: [key => value].
     *
     * @param  callable  $callback  function(mixed $item, mixed $key): array
     * @return static
     *
     * @throws InvalidArgumentException if callback does not return an array
     */
    public function mapWithKeys(callable $callback)
    {
        $results = [];

        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);

            if (!is_array($assoc)) {
                throw new InvalidArgumentException(
                    "mapWithKeys callback must return an array with a single key/value pair."
                );
            }

            foreach ($assoc as $mapKey => $mapValue) {
                $results[$mapKey] = $mapValue;
            }
        }

        return new static($results);
    }

    /**
     * Get the values of a given key.
     *
     * @param  string|int|array<array-key, string>  $value
     * @param  string|null  $key
     * @return static
     */
    public function pluck($value, $key = null)
    {
        $results = [];

        // Resolve nested values from arrays/ArrayAccess/objects.
        // - $path may be a string (supports dot notation), int, or array of segments
        $resolve = function ($target, $path) {
            if (is_array($path)) {
                $segments = $path;
            } elseif (is_string($path)) {
                // Support dot notation transparently
                $segments = explode('.', $path);
            } else {
                $segments = [$path];
            }

            $current = $target;

            foreach ($segments as $segment) {
                if (is_array($current) || $current instanceof \ArrayAccess) {
                    // Avoid illegal offset types
                    if (!is_int($segment) && !is_string($segment)) {
                        return null;
                    }

                    if (is_array($current)) {
                        $current = array_key_exists($segment, $current) ? $current[$segment] : null;
                    } else { // ArrayAccess
                        $current = isset($current[$segment]) ? $current[$segment] : null;
                    }
                } elseif (is_object($current)) {
                    $current = $current->$segment ?? null;
                } else {
                    return null;
                }

                if ($current === null) {
                    break;
                }
            }

            return $current;
        };

        foreach ($this->items as $item) {
            $itemValue = $resolve($item, $value);
            $itemKey = $key !== null ? $resolve($item, $key) : null;

            if ($key !== null && $itemKey !== null) {
                $results[$itemKey] = $itemValue;
            } else {
                $results[] = $itemValue;
            }
        }

        return new static($results);
    }

    /**
     * Pluck values from the collection with support for dot notation.
     *
     * @param  string $key
     * @return static
     */
    public function pluckDot(string $key)
    {
        $results = [];

        foreach ($this->items as $item) {
            $value = $item;

            foreach (explode('.', $key) as $segment) {
                if (is_array($value) && array_key_exists($segment, $value)) {
                    $value = $value[$segment];
                } else {
                    $value = null;
                    break;
                }
            }

            $results[] = $value;
        }

        return new static($results);
    }

    /**
     * Group items in the collection by a given key or callback.
     *
     * @param  string|callable $key
     * @return static
     */
    public function groupBy($key)
    {
        $results = [];

        foreach ($this->items as $item) {
            $groupKey = is_callable($key)
                ? $key($item)
                : (is_array($item) ? ($item[$key] ?? null) : null);

            $results[$groupKey][] = $item;
        }

        return new static($results);
    }

    /**
     * Search for a value in the collection.
     *
     * @param  mixed $value
     * @param  bool $strict
     * @return int|string|false
     */
    public function search(mixed $value, bool $strict = false)
    {
        // If strict is false, perform case-insensitive search
        if (!$strict) {
            foreach ($this->items as $key => $item) {
                if (is_string($item) && strcasecmp($item, $value) === 0) {
                    return $key; // Return the key of the matched item
                } elseif (is_array($item)) {
                    // Perform a case-insensitive search on array values
                    foreach ($item as $subKey => $subItem) {
                        if (is_string($subItem) && strcasecmp($subItem, $value) === 0) {
                            return $key; // Return the key of the matched item
                        }
                    }
                }
            }
        } else {
            return array_search($value, $this->items, true); // Strict case-sensitive search
        }

        return false; // Return false if no match is found
    }

    /**
     * Sort the collection items.
     *
     * @param  callable|null|int $callback
     * @return static
     */
    public function sort($callback = null)
    {
        $items = $this->items;

        $callback && is_callable($callback)
            ? uasort($items, $callback)
            : asort($items, $callback ?? SORT_REGULAR);

        return new static($items);
    }

    /**
     * Sort the collection items by a given key.
     *
     * @param  callable|string $callable
     * @param  int $direction
     * @return static
     */
    public function sortBy(callable|string $callable, int $direction = SORT_ASC)
    {
        $sorted = $this->items;
        uasort($sorted, function ($a, $b) use ($callable, $direction) {
            $valueA = is_callable($callable) ? $callable($a) : $a[$callable];
            $valueB = is_callable($callable) ? $callable($b) : $b[$callable];

            return $direction === SORT_ASC 
                    ? $valueA <=> $valueB 
                    : $valueB <=> $valueA;
        });

        return new static($sorted);
    }

    /**
     * Sort the collection by multiple keys.
     *
     * @param  array $keys
     * @return static
     */
    public function sortByMany(array $keys)
    {
        $sorted = $this->items;
        uasort($sorted, function ($a, $b) use ($keys) {
            foreach ($keys as $key => $direction) {
                $valueA = $a[$key];
                $valueB = $b[$key];
                $comparison = $valueA <=> $valueB;

                if ($comparison !== 0) {
                    return $direction === SORT_ASC ? $comparison : -$comparison;
                }
            }
            return 0;
        });

        return new static($sorted);
    }

    /**
     * Sort the collection in descending order using a callback or key.
     *
     * @param callable|string|null $callback
     * @return static
     */
    public function sortByDesc($callback = null)
    {
        $items = $this->items;

        if (is_null($callback)) {
            arsort($items);  // Preserve keys when sorting values
        } else {
            // Preserve keys when sorting with callback
            uasort($items, fn($a, $b) => $this->valueForSort($b, $callback) <=> $this->valueForSort($a, $callback));
        }

        return new static($items);
    }

    /**
     * Sort the collection by keys ascending.
     *
     * @param string $sort
     * @return static
     */
    public function sortKeys($sort = 'asc')
    {
        $items = $this->items;

        if($sort == 'asc'){
            ksort($items);
        } else{
            krsort($items);
        }

        return new static($items);
    }

    /**
     * Sort the collection by keys descending.
     *
     * @return static
     */
    public function sortKeysDesc()
    {
        return $this->sortKeys('desc');
    }

    /**
     * Key the collection by a given key or callback.
     *
     * @param callable|string $key
     * @return static
     */
    public function keyBy($key)
    {
        $results = [];
        foreach ($this->items as $item) {
            $resolvedKey = is_callable($key) ? $key($item) : $this->dataGet($item, $key);
            $results[$resolvedKey] = $item;
        }
        return new static($results);
    }

    /**
     * Get a slice of the collection.
     *
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function slice(int $offset, ?int $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Take the first n items.
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit)
    {
        return $this->slice(0, $limit);
    }

    /**
     * Take items until the callback returns true.
     *
     * @param callable $callback
     * @return static
     */
    public function takeUntil(callable $callback)
    {
        $results = [];
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) break;
            $results[$key] = $item;
        }
        return new static($results);
    }

    /**
     * Skip the first n items.
     *
     * @param int $count
     * @return static
     */
    public function skip(int $count)
    {
        return $this->slice($count);
    }

    /**
     * Concatenate another array or collection.
     *
     * @param iterable $items
     * @return static
     */
    public function concat($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Union with another array or collection, preserving keys.
     *
     * @param iterable $items
     * @return static
     */
    public function union($items)
    {
        return new static($this->items + $this->getArrayableItems($items));
    }

    /**
     * Get underlying items as a plain collection (same class here, but can differ).
     *
     * @return static
     */
    public function toBase()
    {
        return new static($this->items);
    }

    /**
     * Pipe the collection through a callback.
     *
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Cross join the collection with other arrays or collections.
     *
     * @param array ...$arrays
     * @return static
     */
    public function crossJoin(...$arrays)
    {
        $results = [[]];

        foreach (array_merge([$this->items], $arrays) as $items) {
            $tmp = [];
            foreach ($results as $product) {
                foreach ($this->getArrayableItems($items) as $item) {
                    $tmp[] = array_merge($product, [$item]);
                }
            }
            $results = $tmp;
        }

        return new static($results);
    }

    /**
     * Join the collection items into a string with separator.
     *
     * @param string $glue
     * @return string
     */
    public function join(string $glue = ',')
    {
        return implode($glue, $this->items);
    }

    /**
     * Get unique items from the collection.
     *
     * @return static
     */
    public function unique()
    {
        // Ensure uniqueness based on the entire array (if needed)
        $uniqueItems = array_map("unserialize", array_unique(array_map("serialize", $this->items)));

        return new static($uniqueItems);
    }

    /**
     * Iterate over items and apply a callback.
     *
     * @param callable $callback
     * @return void
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }
    }

    /**
     * Remove data from the items collection
     *
     * @param mixed $keys
     * @return static
     */
    public function forget(...$keys)
    {
        return new static(Str::forgetArrayKeys($this->items, $keys));
    }

    /**
     * Remove data from the items collection
     *
     * @param string $key The key to use for conversion.
     * @param int $case The case sensitivity option for key comparison (upper, lower).
     * @return static
     */
    public function changeKeyCase(string $key, $case = null)
    {
        return new static(Str::changeKeyCase($this->items, $key, $case));
    }

    /**
     * Reduce the collection to a single value using a callback.
     *
     * @param  callable $callback
     * @param  mixed $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Shuffle the items in the collection.
     *
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;
        shuffle($items);
        
        return new static($items);
    }

    /**
     * Partition the collection into two collections based on a callback.
     *
     * @param callable $callback
     * @return array [Collection, Collection]
     */
    public function partition(callable $callback)
    {
        $matches = [];
        $nonMatches = [];
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $matches[$key] = $item;
            } else {
                $nonMatches[$key] = $item;
            }
        }

        return [new static($matches), new static($nonMatches)];
    }

    /**
     * Tap into the collection and perform actions, then return the collection.
     *
     * @param callable $callback
     * @return static
     */
    public function tap(callable $callback)
    {
        $callback($this);

        return $this;
    }

    /**
     * Chunk the collection while a condition is true.
     *
     * @param callable $callback
     * @return static
     */
    public function chunkWhile(callable $callback)
    {
        $chunks = [];
        $chunk = [];
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                $chunk[$key] = $item;
            } else {
                if ($chunk) {
                    $chunks[] = $chunk;
                    $chunk = [];
                }
                $chunk[$key] = $item;
            }
        }
        if ($chunk) {
            $chunks[] = $chunk;
        }
        return new static($chunks);
    }

    /**
     * Get every n-th item from the collection.
     *
     * @param int $step
     * @param int $offset
     * @return static
     */
    public function nth(int $step, int $offset = 0)
    {
        $results = [];
        $position = 0;
        foreach ($this->items as $key => $item) {
            if ($position % $step === $offset) {
                $results[$key] = $item;
            }
            $position++;
        }

        return new static($results);
    }

    /**
     * Paginate the collection items.
     *
     * @param int $perPage
     * @param int $page
     * @return static
     */
    public function paginate(int $perPage, int $page = 1)
    {
        $offset = ($page - 1) * $perPage;
        $items = array_slice($this->items, $offset, $perPage, true);

        return new static($items);
    }

    /**
     * Zip the collection with another collection using a callback.
     *
     * @param array $array
     * @param callable $callback
     * @return static
     */
    public function zipWith(array $array, callable $callback)
    {
        $results = [];
        $count = min(count($this->items), count($array));
        $keys = array_keys($this->items);
        for ($i = 0; $i < $count; $i++) {
            $results[$keys[$i]] = $callback($this->items[$keys[$i]], $array[$i]);
        }
        return new static($results);
    }

    /**
     * Count items grouped by a given key or callback.
     *
     * @param callable|string $groupBy
     * @return static
     */
    public function countBy($groupBy)
    {
        $results = [];
        foreach ($this->items as $key => $item) {
            $group = is_callable($groupBy) ? $groupBy($item, $key) : (is_array($item) ? ($item[$groupBy] ?? null) : null);
            if (!isset($results[$group])) {
                $results[$group] = 0;
            }
            $results[$group]++;
        }
        return new static($results);
    }

    /**
     * Get all duplicate items in the collection.
     *
     * @return static
     */
    public function duplicates()
    {
        $seen = [];
        $duplicates = [];
        foreach ($this->items as $key => $item) {
            $serialized = serialize($item);
            if (isset($seen[$serialized])) {
                $duplicates[$key] = $item;
            } else {
                $seen[$serialized] = true;
            }
        }

        return new static($duplicates);
    }

    /**
     * Shuffle the keys of the collection.
     *
     * @return static
     */
    public function shuffleKeys()
    {
        $keys = array_keys($this->items);
        shuffle($keys);
        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $this->items[$key];
        }

        return new static($shuffled);
    }

    /**
     * Alias for avg() method.
     *
     * @param  string|null $key
     * @return float|int|null
     */
    public function average(?string $key = null)
    {
        return $this->avg($key);
    }

    /**
     * Get the average of the given key or of all items.
     *
     * @param  string|null $key
     * @return float|int|null
     */
    public function avg(?string $key = null)
    {
        $values = $key ? $this->pluckDot($key)->all() : $this->items;

        // Filter only numeric values
        $values = array_filter($values, 'is_numeric');

        return $values ? array_sum($values) / count($values) : null;
    }

    /**
     * Get the sum of the given key or all items.
     *
     * @param  string|null $key
     * @return float|int
     */
    public function sum(?string $key = null)
    {
        $values = $key ? $this->pluckDot($key)->all() : $this->items;
        $values = array_filter($values, 'is_numeric');

        return array_sum($values);
    }

    /**
     * Get the maximum value of the given key or all items.
     *
     * @param  string|null $key
     * @return mixed
     */
    public function max(?string $key = null)
    {
        $values = $key ? $this->pluckDot($key)->all() : $this->items;
        return max($values);
    }

    /**
     * Get the minimum value of the given key or all items.
     *
     * @param  string|null $key
     * @return mixed
     */
    public function min(?string $key = null)
    {
        $values = $key ? $this->pluckDot($key)->all() : $this->items;
        return min($values);
    }

    /**
     * Convert data to array
     * 
     * @return array
     */ 
    public function toArray()
    {
        return Server::toArray($this->items);
    }
    
    /**
     * Convert data to object
     * 
     * @return object
     */ 
    public function toObject()
    {
        return Server::toObject($this->items);
    }
    
    /**
     * Convert data to json
     * 
     * @return string
     */ 
    public function toJson()
    {
        return Server::toJson($this->items);
    }

    /**
     * return items collection as an array
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->toArray();
    }

    /**
     * return items collection as an object
     *
     * @return object
     */
    public function getOriginal()
    {
        return $this->toObject();
    }

    /**
     * Get Database Builder Instance
     *  
     * @return \Tamedevelopers\Database\Schema\Builder
     */
    public function builder()
    {
        return $this->builder;
    }
    
    /**
     * Convert data to an array on Initializaiton
     * @param mixed $items
     * 
     * @return void
     */ 
    private function convertOnInit(mixed $items = null)
    {
        // For ORM Database Proxies and Paginate Data
        // Convert to an array
        if(self::$isBuilder){
            $this->items = $items;
        } elseif($this->isValidJson($items)) {
            $this->items = json_decode($items, true);
        } elseif($this->isNotValidArray($items)){
            $this->items = json_decode(json_encode($items), true);
        } 

        $this->items = $items;
    }

    /**
     * Check if data is not a valid array
     *
     * @param mixed $array
     * @return bool
     */
    private function isNotValidArray(mixed $array = null)
    {
        if (!is_array($array)) {
            return true;
        }

        // array filter
        $filteredArray = array_filter($array, 'is_array');
    
        return count($filteredArray) === count($array);
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param mixed $data
     * @return bool
     */
    private function isValidJson(mixed $data = null)
    {
        if(is_string($data)){
            json_decode($data);
            return json_last_error() === JSON_ERROR_NONE;
        }

        return false;
    }

}