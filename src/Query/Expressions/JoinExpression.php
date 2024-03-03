<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;

/**
 * @property array<int|string, JoinClauseExpression> $expressions
 */
class JoinExpression extends ExpressionSet
{
    public function add(JoinClauseExpression $expression): static
    {
        $i = $this->count();
        $key = $expression->getAs() ?: $i++;

        $this->expressions[$key] = $expression;

        return $this;
    }

    public function get(int|string $key): JoinClauseExpression
    {
        return $this->expressions[$key] ?? throw QueryException::noJoinNamed($key);
    }
}
