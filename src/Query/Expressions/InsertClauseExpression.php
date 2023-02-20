<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class InsertClauseExpression implements ExpressionInterface
{
    protected ExpressionInterface|string $table;
    protected ModifierExpression $modifier;
    protected array $columns;

    public function __construct(ExpressionInterface|string $table = null, array $columns = [])
    {
        $this->table = $table ?? '';
        $this->columns = $columns;
        $this->modifier = new ModifierExpression();
    }

    public function into(ExpressionInterface|string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function modifier(): ModifierExpression
    {
        return $this->modifier;
    }

    public function getTable(): ExpressionInterface|string
    {
        return $this->table;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        if ($this->table instanceof ExpressionInterface) {
            $callback($this->table);
            $this->table->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        if ($this->table instanceof ExpressionInterface) {
            $this->table = clone $this->table;
        }
    }
}
