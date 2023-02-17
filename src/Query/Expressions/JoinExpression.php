<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;

/**
 * @property array<int|string, JoinClauseExpression> $expressions
 */
class JoinExpression extends ExpressionSet
{
    public function add(JoinClauseExpression $join): static
    {
        $i = $this->count();
        $key = $join->getAs() ?: $i++;

        $this->expressions[$key] = $join;

        return $this;
    }

    public function get(int|string $key): JoinClauseExpression
    {
        return $this->expressions[$key] ?? throw QueryException::noJoinNamed($key);
    }
}
