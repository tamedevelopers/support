<?php

declare(strict_types=1);

namespace Tamedevelopers\Support\Traits;


use Tamedevelopers\Support\Expression;

trait ExpressionTrait{
    
    /**
     * Create a raw SQL expression.
     *
     * @param string $expression
     * 
     * @return Tamedevelopers\Support\Expression
     */
    public static function raw($expression)
    {
        return new Expression($expression);
    }

}
