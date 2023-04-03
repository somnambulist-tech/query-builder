<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Field;
use Somnambulist\Components\QueryBuilder\Query\OrderDirection;

/**
 * An expression object for complex ORDER BY clauses
 */
class OrderClauseExpression implements Expression, Field
{
    public function __construct(
        protected Expression|string $field,
        protected OrderDirection $direction
    ) {
    }

    public function field(Expression|array|string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function direction(OrderDirection $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function asc(): self
    {
        $this->direction = OrderDirection::ASC;

        return $this;
    }

    public function desc(): self
    {
        $this->direction = OrderDirection::DESC;

        return $this;
    }

    public function getField(): Expression|array|string
    {
        return $this->field;
    }

    public function getDirection(): OrderDirection
    {
        return $this->direction;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->field instanceof Expression) {
            $callback($this->field);
            $this->field->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->field instanceof Expression) {
            $this->field = clone $this->field;
        }
    }
}
