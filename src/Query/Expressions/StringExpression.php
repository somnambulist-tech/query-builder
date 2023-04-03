<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;

/**
 * String expression with collation.
 */
class StringExpression implements Expression
{
    public function __construct(
        protected string $string,
        protected string $collation
    ) {
    }

    public function string(string $string): self
    {
        $this->string = $string;

        return $this;
    }

    public function collate(string $collation): self
    {
        $this->collation = $collation;

        return $this;
    }

    public function getString(): string
    {
        return $this->string;
    }

    public function getCollation(): string
    {
        return $this->collation;
    }

    public function traverse(Closure $callback): self
    {
        return $this;
    }
}
