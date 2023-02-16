<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\JoinType;

class JoinClauseExpression implements ExpressionInterface
{
    public function __construct(
        protected string $alias,
        protected ExpressionInterface $table,
        protected ExpressionInterface $conditions,
        protected JoinType $type,
    ) {
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function getTable(): ExpressionInterface
    {
        return $this->table;
    }

    public function setTable(ExpressionInterface $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function getConditions(): ExpressionInterface
    {
        return $this->conditions;
    }

    public function setConditions(ExpressionInterface $conditions): self
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function getType(): JoinType
    {
        return $this->type;
    }

    public function setType(JoinType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->table);
        $this->table->traverse($callback);

        $callback($this->conditions);
        $this->conditions->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        $this->table = clone $this->table;
        $this->conditions = clone $this->conditions;
    }
}
