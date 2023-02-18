<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

class NamedWindowClauseExpression implements ExpressionInterface
{
    public function __construct(
        protected IdentifierExpression $name,
        protected WindowExpression $window
    ) {
    }

    public function getName(): IdentifierExpression
    {
        return $this->name;
    }

    public function setName(IdentifierExpression $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getWindow(): WindowExpression
    {
        return $this->window;
    }

    public function setWindow(WindowExpression $window): self
    {
        $this->window = $window;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->name);
        $this->name->traverse($callback);

        $callback($this->window);
        $this->window->traverse($callback);

        return $this;
    }

    public function __clone(): void
    {
        $this->name = clone $this->name;
        $this->window = clone $this->window;
    }
}
