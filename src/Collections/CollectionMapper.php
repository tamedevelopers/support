<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections;

use ArrayAccess;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Tamedevelopers\Support\Collections\Collection;
use Tamedevelopers\Support\Collections\CollectionProperty;
use Tamedevelopers\Support\Collections\Traits\RelatedTrait;

class CollectionMapper extends CollectionProperty implements IteratorAggregate, ArrayAccess
{
    use RelatedTrait;

    /**
     * Array index key
     */
    protected mixed $key;
    
    /**
     * Create a new collection.
     *
     * @param  mixed $items
     * @param  mixed $key
     * @param  Collection $collection
     */
    public function __construct(mixed $items = [], mixed $key = 0, $collection = null)
    {
        $this->convertOnArrayLoop($items);

        $this->key          = ((int) $key + 1);
        self::$builder      = $collection?->builder;
        self::$isPaginate   = $collection?->isPaginate;
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get Pagination Numbers
     */
    public function numbers(): int
    {
        if(self::$isPaginate){
            return (self::$builder->pagination->offset + $this->key);
        }
        
        return $this->key;
    }

}