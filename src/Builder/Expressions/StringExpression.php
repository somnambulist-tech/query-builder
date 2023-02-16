<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;

/**
 * String expression with collation.
 */
class StringExpression implements ExpressionInterface
{
    public function __construct(
        protected string $string,
        protected string $collation
    ) {
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): self
    {
        $this->string = $string;

        return $this;
    }

    public function getCollation(): string
    {
        return $this->collation;
    }

    public function setCollation(string $collation): self
    {
        $this->collation = $collation;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        return $this;
    }
}
