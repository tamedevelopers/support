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
     * @param array|null $items
     * 
     * @param mixed $instance
     * - [optional] Used on ORM Database Only
     * Meant for easy manupulation of collection instance
     * This doesn't have affect on using this the Collection class on other projects
     */
    public function __construct($items = [], $instance = null)
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
    public function getIterator(): Traversable
    {
        return new ArrayIterator(
            $this->wrapArrayIntoNewCollections()
        );
    }

    /**
     * Get Pagination Links
     * 
     * @param array{
     *  first: string,
     *  last: string,
     *  next: string,
     *  prev: string,
     *  showing: string,
     *  of: string,
     *  results: string,
     *  buttons: int<1, 20>,
     *  load_more: string,
     *  no_content: string
     * } $options
     *
     * @return string
     */
    public function links($options = [])
    {
        $this->buildPagination($options);
    }

    /**
     * Format Pagination Data
     * 
     * @param array $options
     * @return string
     */
    public function showing($options = [])
    {
        if(self::$isPaginate){
            self::$paginator->showing($options);
        }
    }

    /**
     * With this helper we're able to build support
     * for multiple pagination on same page without conflicts
     * 
     * @param array $options
     * @return mixed
     */
    public function buildPagination($options = [])
    {
        if(self::$isPaginate){
            $paginator = self::$paginator;

            $paginator->pagination->pageParam     = $paginator->pageParam;
            $paginator->pagination->perPageParam  = $paginator->perPageParam;
            
            $paginator->links($options);
        }
    }

}