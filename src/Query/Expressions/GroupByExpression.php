<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;

/**
 * @property array<int, ExpressionInterface|string> $expressions
 */
class GroupByExpression extends ExpressionSet
{
    public function add(ExpressionInterface|string ...$expression): static
    {
        $this->expressions = array_merge($this->expressions, $expression);

        return $this;
    }

    public function get(int|string $key): ExpressionInterface|string
    {
        return $this->expressions[$key] ?? throw QueryException::noExpressionFor($key);
    }
}
