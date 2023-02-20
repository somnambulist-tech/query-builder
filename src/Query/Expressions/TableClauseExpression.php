<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class TableClauseExpression implements ExpressionInterface
{
    public function __construct(
        protected ExpressionInterface $table,
        protected ?string $as = null,
    ) {
    }

    public function table(ExpressionInterface $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function as(?string $as): self
    {
        $this->as = $as;

        return $this;
    }

    public function getTable(): ExpressionInterface
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->as;
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->table);
        $this->table->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        $this->table = clone $this->table;
    }
}
