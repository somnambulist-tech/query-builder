<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class FieldClauseExpression implements ExpressionInterface
{
    public function __construct(
        protected ExpressionInterface|string|float|int $field,
        protected ?string $as = null,
    ) {
    }

    public function field(ExpressionInterface|string|float|int $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function as(?string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function getField(): ExpressionInterface|string|float|int
    {
        return $this->field;
    }

    public function getAlias(): ?string
    {
        return $this->as;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->field instanceof ExpressionInterface) {
            $callback($this->field);
            $this->field->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        if ($this->field instanceof ExpressionInterface) {
            $this->field = clone $this->field;
        }
    }
}
