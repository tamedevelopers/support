<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections;

use ArrayAccess;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Collections\CollectionTrait;


class Collection implements IteratorAggregate, ArrayAccess
{
    use CollectionTrait;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param  array $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }
    
    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator($this->items);
    }

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
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return is_array($this->items) 
                ? count($this->items)
                : 0;
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
     * Get the first item in the collection.
     *
     * @return mixed
     */
    public function first()
    {
        return Str::head($this->items);
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
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Determine if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
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
        return new static(
            Str::flattenValue($this->items)
        );
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
     * Pluck a specific field from each item in the collection.
     *
     * @param  string|array $key
     * @return static
     */
    public function pluck(string|array $key)
    {
        $results = [];

        foreach ($this->items as $item) {
            if (is_array($item) || $item instanceof \ArrayAccess) {
                $results[] = is_array($key) ? array_intersect_key($item, array_flip((array) $key)) : ($item[$key] ?? null);
            }
        }

        return new static($results);
    }

    /**
     * Select specific keys from the collection.
     *
     * @param  array $keys
     * @return static
     */
    public function select(array $keys)
    {
        $selected = array_map(function ($item) use ($keys) {
            // If the item is an array, use array_intersect_key to select keys
            if (is_array($item)) {
                return array_intersect_key($item, array_flip($keys));
            }
    
            // If the item is not an array, just return it
            return $item;
        }, $this->items);
    
        return new static($selected);
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
        usort($sorted, function ($a, $b) use ($callable, $direction) {
            $valueA = is_callable($callable) ? $callable($a) : $a[$callable];
            $valueB = is_callable($callable) ? $callable($b) : $b[$callable];

            return $direction === SORT_ASC ? $valueA <=> $valueB : $valueB <=> $valueA;
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
        usort($sorted, function ($a, $b) use ($keys) {
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

}