<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Capsule;

use Tamedevelopers\Support\Traits\FileTrait;

class FileBag
{
    use FileTrait;

    /**
     * Collection of files.
     *
     * @var array<string, mixed>
     */
    protected $collection = [];

    /**
     * File name.
     *
     * @var string|null
     */
    protected static $name;

    /**
     * Constructor.
     * 
     * @param array<string, mixed>|null $collection
     */
    public function __construct(?array $collection = null) 
    {
        if(!empty($collection)){
            $this->collection = $collection;
        }
    }
}