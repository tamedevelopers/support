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
    protected mixed $collection;
    
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

        $this->key = ((int) $key + 1);
        $this->collection = $collection;
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
        $collection = $this->collection;

        if($collection::$isPaginate){
            $paginator = $collection::$paginator;
            $this->key = ($paginator->pagination->offset + $this->key);
        }
        
        return $this->key;
    }

}