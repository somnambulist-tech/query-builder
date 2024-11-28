<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;
use Somnambulist\Components\QueryBuilder\Query\Query;
use function array_key_exists;
use function is_null;
use function is_string;

/**
 * @property array<int, TableClauseExpression> $expressions
 */
class FromExpression extends ExpressionSet
{
    public function add(Expression|string $expression, ?string $as = null): self
    {
        if ($expression instanceof Query && is_null($as)) {
            throw QueryException::fromQueryRequiresAlias();
        }
        if (is_string($expression)) {
            $expression = new IdentifierExpression($expression);
        }

        $this->expressions[] = new TableClauseExpression($expression, $as);

        return $this;
    }

    /**
     * Returns true if the table is part of the current from expression or is a named alias
     *
     * @param string $table
     *
     * @return bool
     */
    public function references(string $table): bool
    {
        foreach ($this as $key => $value) {
            if (is_string($key) && $key === $table) {
                return true;
            }
            if ($value instanceof IdentifierExpression) {
                if ($value->getIdentifier() === $table) {
                    return true;
                }
            }
        }

        return false;
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

    public function get(int|string $key): TableClauseExpression
    {
        if (array_key_exists($key, $this->expressions)) {
            return $this->expressions[$key];
        }

        foreach ($this->expressions as $e) {
            if ($e->getAlias() === $key) {
                return $e;
            }
        }

        throw QueryException::noFromClauseFor($key);
    }
}
