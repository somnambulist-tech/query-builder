<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class UpdateClauseExpression implements ExpressionInterface
{
    protected ExpressionInterface|string $table;
    protected ModifierExpression $modifier;

    public function __construct(ExpressionInterface|string $table = null, ModifierExpression $modifier = null)
    {
        $this->table = $table ?? '';
        $this->modifier = $modifier ?? new ModifierExpression();
    }

    public function table(ExpressionInterface|string $table): self
    {
        $this->table = $table;

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

    public function traverse(Closure $callback): ExpressionInterface
    {
        if ($this->table instanceof ExpressionInterface) {
            $callback($this->table);
            $this->table->traverse($callback);
        }

        $callback($this->modifier);
        $this->modifier->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        if ($this->table instanceof ExpressionInterface) {
            $this->table = clone $this->table;
        }

        $this->modifier = clone $this->modifier;
    }
}
