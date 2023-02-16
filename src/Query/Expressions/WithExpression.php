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

class WithExpression implements Countable, ExpressionInterface, IteratorAggregate
{
    /**
     * @var array<CommonTableExpression>
     */
    private array $expressions;

    public function __construct(array $expressions = [])
    {
        $this->expressions = $expressions;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->expressions);
    }

    public function count(): int
    {
        return count($this->expressions);
    }

    public function add(CommonTableExpression $cte): self
    {
        $this->expressions[] = $cte;

        return $this;
    }

    public function has(string $alias): bool
    {
        foreach ($this->expressions as $cte) {
            if ($cte->getName() === $alias) {
                return true;
            }
        }

        return false;
    }

    public function get(int|string $alias): CommonTableExpression
    {
        if (array_key_exists($alias, $this->expressions)) {
            return $this->expressions[$alias];
        }

        foreach ($this->expressions as $cte) {
            if ($cte->getName() === $alias) {
                return $cte;
            }
        }

        throw QueryException::noWithExpressionNamed($alias);
    }

    public function remove(string $alias): self
    {
        foreach ($this->expressions as $k => $cte) {
            if ($cte->getName() === $alias) {
                unset($this->expressions[$k]);
                break;
            }
        }

        return $this;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        foreach ($this->expressions as $e) {
            $callback($e);
            $e->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        foreach ($this->expressions as $key => $e) {
            $this->expressions[$key] = clone $e;
        }
    }
}
