<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Collections\Traits;

use Closure;
use Exception;
use Traversable;
use JsonSerializable;
use Tamedevelopers\Support\Str;
use Tamedevelopers\Support\Collections\CollectionMapper;


/**
 * @property bool $isProxyAllowed
 * @property bool $isPaginate
 * @property bool $isBuilder
 * @property array $proxies
 * @property mixed $builder
 * @property mixed $paginator
 */
trait CollectionTrait{

    /**
     * Get Pagination Data
     * 
     * @return object|void
     */
    public function getPagination()
    {
        if(self::$isPaginate){
            $pagination = self::$paginator->pagination;
            return (object) [ 
                'limit'         => (int) $pagination->limit,
                'offset'        => (int) $pagination->offset,
                'page'          => (int) $pagination->page,
                'pageCount'     => (int) $pagination->pageCount,
                'perPage'       => (int) $pagination->perPage,
                'totalCount'    => (int) $pagination->totalCount,
            ];
        }
    }

    /**
     * Convert arrays into instance of Collection
     * 
     * @return mixed
     */
    protected function wrapArrayIntoNewCollections()
    {
        // check if valid array data
        if (!$this->isProxyAllowed && is_array($this->items) && !empty($this->items)) {
            return array_map(function ($item, $key){
                return new CollectionMapper($item, $key, $this);
            }, $this->items, array_keys($this->items));
        }
        
        return $this->items;
    }

    /**
     * Check Proxies Type
     * Determine and get ORM Database Method/Function request
     */
    protected function isProxies(): void
    {
        if(self::$isBuilder){
            $methodName = Str::lower(self::$builder->method);

            if(in_array($methodName, self::$proxies)){
                $this->isProxyAllowed = true;
            }
        }

        // reset builder and paginator instance if proxy is allowed
        if($this->isProxyAllowed){
            self::$builder = null;
            self::$paginator = null;
        }
    }
    
    /**
     * Get Instance of ORM Builder or Paginator
     * @param  mixed $expression
     * @return void
     */
    protected function isBuilderOrPaginator($expression = null)
    {
        $paginatorInstance  = '\Tamedevelopers\Database\Schema\Pagination\Paginator';
        $builderInstance    = '\Tamedevelopers\Database\Schema\Builder';

        // Check if the expression is an instance of the builder or paginator
        self::$isBuilder = $expression instanceof $builderInstance;

        // Check if the expression is an instance of the paginator
        self::$isPaginate = $expression instanceof $paginatorInstance;

        if(self::$isBuilder){
            self::$builder = $expression;
        }

        if(self::$isPaginate){
            self::$paginator = $expression;
        }
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }

        if(empty($items) || is_null($items)){
            return [];
        }

        if(is_object($items) && method_exists($items, 'toArray')){
            return $items->toArray();
        }

        return match (true) {
            $items instanceof Traversable => iterator_to_array($items),
            $items instanceof JsonSerializable => $items->jsonSerialize(),
            $this->isValidJson($items) => json_decode($items, true),
            default => (array) $items,
        };
    }

    /**
     * Determine if the given operator is supported.
     *
     * @param  string  $operator
     * @return bool
     */
    protected function invalidOperator($operator): bool
    {
        return !is_string($operator)
            || !in_array(strtolower($operator), array_map('strtolower', $this->operators()), true);
    }

    /**
     * Check illegal operator/value combinations.
     *
     * @param  string|null $operator
     * @param  mixed $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value): bool
    {
        return is_null($value) && in_array($operator, ['=', '<', '>', '<=', '>=', '!=']);
    }

    /**
     * Prepare the value and operator for a where clause.
     *
     * @param  string|null $value
     * @param  string|null $operator
     * @param  bool $useDefault
     * @return array{mixed,string}
     *
     * @throws \Exception
     */
    protected function prepareValueAndOperator($value, $operator, $useDefault = false): array
    {
        if ($useDefault) {
            return [$operator, '='];
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new Exception("Illegal operator and value combination. `{$operator}`");
        }

        // fallback to equals if invalid
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$value, '='];
        }

        return [$value, strtolower($operator)];
    }

    /**
     * Compare actual value against another using an operator.
     *
     * @param  mixed $actual
     * @param  mixed $value
     * @param  string $operator
     * @return bool
     */
    protected function compare($actual, $value, string $operator): bool
    {
        switch ($operator) {
            case '=': 
            case '==': 
                return $actual == $value;
            case '!=':
            case '<>': 
                return $actual != $value;
            case '<':  
                return $actual < $value;
            case '>':  
                return $actual > $value;
            case '<=': 
                return $actual <= $value;
            case '>=': 
                return $actual >= $value;
            case '<=>': 
                return ($actual <=> $value) === 0;
            case 'like': 
                return (bool) preg_match('/' . str_replace('%', '.*', preg_quote($value, '/')) . '/i', (string) $actual);
            case 'not like': 
                return !(bool) preg_match('/' . str_replace('%', '.*', preg_quote($value, '/')) . '/i', (string) $actual);
            case 'is': 
                return $actual === $value;
            case 'is not': 
                return $actual !== $value;
            // default: return false;
            default: throw new \InvalidArgumentException("Unsupported operator [{$operator}].");;
        }
    }

    /**
     * Evaluate a where clause condition for a given item.
     *
     * @param  array|object $item
     * @param  string|Closure $key
     * @param  string|null $operator
     * @param  mixed $value
     * @return bool
     */
    protected function evaluateWhere($item, $key, $operator = null, $value = null): bool
    {
        if (is_callable($key)) {
            return $key($item);
        }

        $actual = $this->dataGet($item, $key);

        return $this->compare(
            $actual, 
            $value, 
            $operator
        );
    }

    /**
     * Simple dot-notation getter (like Laravel's data_get).
     *
     * @param array|object $target
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function dataGet($target, string $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        // Handle Closure default
        $default = $default instanceof \Closure ? $default() : $default;

        foreach (explode('.', $key) as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * Helper for sorting by callback/key.
     *
     * @param mixed $value
     * @param Closure|string $callback
     * @return mixed
     */
    protected function valueForSort($value, $callback)
    {
        return is_callable($callback) ? $callback($value) : $this->dataGet($value, $callback);
    }

    /**
     * List of supported operators.
     *
     * @return array
     */
    protected function operators()
    {
        return [
            "=",
            "<",
            ">",
            ">=",
            "<=",
            "!=",
            "<>",
            "<=>",
            "&",
            "|",
            "<<",
            ">>",
            "like",
            "not like",
            "is",
            "is not",
        ];
    }
    
}