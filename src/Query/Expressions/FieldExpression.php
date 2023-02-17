<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;
use function array_key_exists;

/**
 * @property array<int, FieldClauseExpression> $expressions
 */
class FieldExpression extends ExpressionSet
{
    public function add(FieldClauseExpression $expression): static
    {
        $this->expressions[] = $expression;

        return $this;
    }

    public function has(int|string $key): bool
    {
        if (array_key_exists($key, $this->expressions)) {
            return true;
        }

        foreach ($this->expressions as $cte) {
            if ($cte->getAlias() === $key) {
                return true;
            }
        }

        return false;
    }

    public function get(int|string $key): FieldClauseExpression
    {
        if (array_key_exists($key, $this->expressions)) {
            return $this->expressions[$key];
        }

        foreach ($this->expressions as $e) {
            if ($e->getAlias() === $key) {
                return $e;
            }
        }

        throw QueryException::noFieldFor($key);
    }
}
