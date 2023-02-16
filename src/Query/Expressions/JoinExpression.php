<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Traversable;
use function array_key_exists;
use function count;

class JoinExpression implements Countable, ExpressionInterface, IteratorAggregate
{
    /**
     * @var array<string, JoinClauseExpression>
     */
    private array $joins;

    public function __construct(array $joins = [])
    {
        $this->joins = $joins;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->joins);
    }

    public function count(): int
    {
        return count($this->joins);
    }

    public function add(JoinClauseExpression $join): self
    {
        $i = $this->count();
        $key = $join->getAlias() ?: $i++;

        $this->joins[$key] = $join;

        return $this;
    }

    public function has(string $alias): bool
    {
        return array_key_exists($alias, $this->joins);
    }

    public function get(string $alias): JoinClauseExpression
    {
        return $this->joins[$alias] ?? throw QueryException::noJoinNamed($alias);
    }

    public function remove(string $alias): self
    {
        unset($this->joins[$alias]);

        return $this;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        foreach ($this->joins as $join) {
            $callback($join);
            $join->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        foreach ($this->joins as $key => $join) {
            $this->joins[$key] = clone $join;
        }
    }
}
