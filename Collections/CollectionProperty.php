<?php

declare(strict_types=1);

/*
 * This file is part of ultimate-orm-database.
 *
 * (c) Tame Developers Inc.
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tamedevelopers\Support\Collections;


class CollectionProperty
{

    /**
     * Instance of ORM Database Class
     *
     * @var \Tamedevelopers\Database\Schema\Builder
     */
    protected $builder;

    /**
     * The items of collections.
     *
     * @var mixed
     */
    protected $items = [];

    /**
     * Check if data is proxy set
     *
     * @var bool
     */
    protected $isProxyAllowed = false;

    /**
     * If Instance of Database Pagination Method is true
     * @var mixed
     */
    protected $isPaginate = false;

    /**
     * If Instance of \Tamedevelopers\Database\DB is true
     * @var bool
     */
    protected static $isBuilder = false;

    /**
     * The methods that can be proxied.
     *
     * @var array
     */
    protected static $proxies = [
        'find',
        'first',
        'firstorcreate',
        'firstorfail',
        'insert',
        'insertorignore',
    ];

}