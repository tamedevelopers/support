<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections;

use Traversable;
use JsonSerializable;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Server;
use Tamedevelopers\Database\Collections\Collection as DBCollection;


trait CollectionTrait{

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
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    private function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }

        return match (true) {
            $items instanceof Traversable => iterator_to_array($items),
            $items instanceof JsonSerializable => $items->jsonSerialize(),
            $items instanceof DBCollection => $items->toArray(),
            default => (array) $items,
        };
    }

}