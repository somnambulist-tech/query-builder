<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\ExpressionSet;

/**
 * @property array<int, Expression|string> $expressions
 */
class DistinctExpression extends ExpressionSet
{
    private bool $row = false;

    public function row(): static
    {
        $this->row = true;

        return $this;
    }

    public function isDistinctRow(): bool
    {
        return $this->row;
    }

    public function on(Expression|string ...$expression): static
    {
        $this->add(...$expression);

        return $this;
    }

    public function add(Expression|string ...$expression): static
    {
        $this->expressions = array_merge($this->expressions, $expression);

        return $this;
    }

    public function get(int|string $key): Expression|string
    {
        return $this->expressions[$key] ?? throw QueryException::noExpressionFor($key);
    }

    public function reset(): static
    {
        $this->row = false;

        return parent::reset();
    }
}
