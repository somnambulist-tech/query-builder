<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class SelectExpression implements ExpressionInterface
{
    private FieldExpression $fields;
    private DistinctExpression $distinct;
    private ModifierExpression $modifier;

    public function __construct(FieldExpression $fields = null, DistinctExpression $distinct = null, ModifierExpression $modifier = null)
    {
        $this->fields = $fields ?? new FieldExpression();
        $this->distinct = $distinct ?? new DistinctExpression();
        $this->modifier = $modifier ?? new ModifierExpression();
    }

    public function fields(): FieldExpression
    {
        return $this->fields;
    }

    public function distinct(): DistinctExpression
    {
        return $this->distinct;
    }

    public function modifier(): ModifierExpression
    {
        return $this->modifier;
    }

    public function reset(): self
    {
        $this->fields->reset();
        $this->distinct->reset();
        $this->modifier->reset();

        return $this;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        $callback($this);

        $callback($this->fields);
        $this->fields->traverse($callback);
        $callback($this->distinct);
        $this->distinct->traverse($callback);
        $callback($this->modifier);
        $this->modifier->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        $this->fields = clone $this->fields;
        $this->distinct = clone $this->distinct;
        $this->modifier = clone $this->modifier;
    }
}
