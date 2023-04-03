<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder;

use Somnambulist\Components\QueryBuilder\Query\Expression;

interface TypeCanCastToExpression
{
    /**
     * Returns an ExpressionInterface object for the given value that can be used in queries.
     *
     * @param mixed $value The value to be converted to an expression
     *
     * @return Expression
     */
    public function toExpression(mixed $value): Expression;
}
