<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\JoinType;

class JoinClauseExpression implements ExpressionInterface
{
    public function __construct(
        protected ExpressionInterface $table,
        protected string $as,
        protected ExpressionInterface $on,
        protected JoinType $type,
    ) {
    }

    public function getTable(): ExpressionInterface
    {
        return $this->table;
    }

    public function table(ExpressionInterface $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function as(string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function on(ExpressionInterface $on): self
    {
        $this->on = $on;

        return $this;
    }

    public function type(JoinType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAs(): string
    {
        return $this->as;
    }

    public function getConditions(): ExpressionInterface
    {
        return $this->on;
    }

    public function getType(): JoinType
    {
        return $this->type;
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->table);
        $this->table->traverse($callback);

        $callback($this->on);
        $this->on->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        $this->table = clone $this->table;
        $this->on = clone $this->on;
    }
}
