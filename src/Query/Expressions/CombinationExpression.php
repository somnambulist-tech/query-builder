<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Query;

abstract class CombinationExpression implements Expression
{
    public function __construct(
        protected Query $query,
        protected bool $all = false,
    ) {
    }

    public function query(Query $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function all(): self
    {
        $this->all = true;

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function useAll(): bool
    {
        return $this->all;
    }

    public function traverse(Closure $callback): Expression
    {
        if ($this->query instanceof Query) {
            $callback($this->query);
            $this->query->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        if ($this->query instanceof Query) {
            $this->query = clone $this->query;
        }
    }
}
