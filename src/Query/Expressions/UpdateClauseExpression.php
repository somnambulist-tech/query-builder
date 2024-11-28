<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;

class UpdateClauseExpression implements Expression
{
    protected Expression|string $table;
    protected ModifierExpression $modifier;

    public function __construct(Expression|string|null $table = null, ?ModifierExpression $modifier = null)
    {
        $this->table = $table ?? '';
        $this->modifier = $modifier ?? new ModifierExpression();
    }

    public function table(Expression|string $table): self
    {
        $this->table = $table;

        return $this;
    }

    public function modifier(): ModifierExpression
    {
        return $this->modifier;
    }

    public function getTable(): Expression|string
    {
        return $this->table;
    }

    public function traverse(Closure $callback): Expression
    {
        if ($this->table instanceof Expression) {
            $callback($this->table);
            $this->table->traverse($callback);
        }

        $callback($this->modifier);
        $this->modifier->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        if ($this->table instanceof Expression) {
            $this->table = clone $this->table;
        }

        $this->modifier = clone $this->modifier;
    }
}
