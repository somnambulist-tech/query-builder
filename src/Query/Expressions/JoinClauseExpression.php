<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\JoinType;

class JoinClauseExpression implements Expression
{
    public function __construct(
        protected Expression $table,
        protected string $as,
        protected Expression $on,
        protected JoinType $type,
    ) {
    }

    public function getTable(): Expression
    {
        return $this->table;
    }

    public function table(Expression $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function as(string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function on(Expression $on): self
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

    public function getConditions(): Expression
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
