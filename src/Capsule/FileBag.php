<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Traits\FileTrait;

class FileBag
{
    use FileTrait;

    /**
     * Collection of file.
     *
     * @var array<string, mixed>
     */
    protected $collection = [];

    /**
     * Collections of files.
     *
     * @var array<string, mixed>
     */
    protected static $collections = [];

    /**
     * File name.
     *
     * @var string|null
     */
    protected static $name;

    /**
     * Constructor.
     * 
     * @param array|string|null $collectionOrInputName
     */
    public function __construct($collectionOrInputName = null) 
    {
        if(!empty($collectionOrInputName)){
            if(is_string($collectionOrInputName)){
                self::$name = $collectionOrInputName;
                $this->collection = self::collect(self::$name)->collection;
            } elseif(is_array($collectionOrInputName)){
                $this->collection = $collectionOrInputName;
            }
        }
    }
}