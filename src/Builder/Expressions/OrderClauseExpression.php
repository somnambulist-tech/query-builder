<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\FieldInterface;
use Somnambulist\Components\QueryBuilder\Builder\OrderDirection;

/**
 * An expression object for complex ORDER BY clauses
 */
class OrderClauseExpression implements ExpressionInterface, FieldInterface
{
    public function __construct(
        protected ExpressionInterface|string $field,
        protected OrderDirection $direction
    ) {
    }

    public function getField(): ExpressionInterface|array|string
    {
        return $this->field;
    }

    public function setField(ExpressionInterface|array|string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getDirection(): OrderDirection
    {
        return $this->direction;
    }

    public function setDirection(OrderDirection $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->field instanceof ExpressionInterface) {
            $callback($this->field);
            $this->field->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->field instanceof ExpressionInterface) {
            $this->field = clone $this->field;
        }
    }
}
