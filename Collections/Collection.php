<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections;

use ArrayAccess;
use Traversable;
use ArrayIterator;
use IteratorAggregate;
use Tamedevelopers\Support\Collections\CollectionProperty;
use Tamedevelopers\Support\Collections\Traits\RelatedTrait;
use Tamedevelopers\Support\Collections\Traits\CollectionTrait;


class Collection extends CollectionProperty implements IteratorAggregate, ArrayAccess
{
    use CollectionTrait, RelatedTrait;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection instance.
     *
     * @param  array $items
     * 
     * @param  mixed $instance
     * - [optional] Used on ORM Database Only
     * Meant for easy manupulation of collection instance
     * This doesn't have affect on using this the Collection class on other projects
     */
    public function __construct($items = [], mixed $instance = null)
    {
        $this->items = $this->getArrayableItems($items);
        $this->isBuilderOrPaginator($instance);
        $this->isProxies();
    }
    
    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator() : Traversable
    {
        return new ArrayIterator(
            $this->wrapArrayIntoNewCollections()
        );
    }

    /**
     * Get Pagination Links
     * @param array $options
     *
     * @return \Tamedevelopers\Database\Schema\Pagination\links()
     */
    public function links(?array $options = [])
    {
        if(isset($this->isPaginate)){
            $this->paginationBuilder();
            $this->builder->links($options);
        }
    }

    /**
     * Format Pagination Data
     * @param array $options
     * 
     * @return \Tamedevelopers\Database\Schema\Pagination\showing()
     */
    public function showing(?array $options = [])
    {
        if(isset($this->isPaginate)){
            $this->builder->showing($options);
        }
    }

    /**
     * With this helper we're able to build support
     * for multiple pagination on same page without conflicts
     * 
     * @return void
     */
    public function paginationBuilder()
    {
        if(isset($this->isPaginate)){
            $this->builder->pagination->pageParam = $this->builder->pageParam;
            $this->builder->pagination->perPageParam = $this->builder->perPageParam;
        }
    }

}