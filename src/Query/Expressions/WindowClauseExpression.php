<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;

/**
 * @property array<int, NamedWindowClauseExpression> $expressions
 */
class WindowClauseExpression extends ExpressionSet
{
    public function add(NamedWindowClauseExpression $expression): self
    {
        $this->expressions[] = $expression;

        return $this;
    }

    public function get(int|string $key): NamedWindowClauseExpression
    {
        return $this->expressions[$key] ?? throw QueryException::noExpressionFor($key);
    }
}
