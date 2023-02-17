<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Traversable;
use function array_key_exists;
use function count;
use function is_object;

/**
 * Generic container for a set of expressions that is itself an expression
 *
 * Extend this set and provide the add / get / remove methods as needed to provide the necessary
 * functionality for a given use-case. Note: a given concrete expression set should never be
 * extended for another.
 *
 * @method static add(ExpressionInterface $expression)
 * @method abstract static get(int|string $key)
 */
abstract class ExpressionSet implements Countable, ExpressionInterface, IteratorAggregate
{
    /**
     * @var array<int|string, ExpressionInterface>
     */
    protected array $expressions = [];

    public function __construct(array $expressions = [])
    {
        foreach ($expressions as $expression) {
            $this->add($expression);
        }
    }

    public function all(): array
    {
        return $this->expressions;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->expressions);
    }

    public function count(): int
    {
        return count($this->expressions);
    }

    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->expressions);
    }

    public function remove(int|string $key): static
    {
        unset($this->expressions[$key]);

        return $this;
    }

    public function reset(): static
    {
        $this->expressions = [];

        return $this;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        foreach ($this->expressions as $e) {
            if ($e instanceof ExpressionInterface) {
                $callback($e);
                $e->traverse($callback);
            }
        }

        return $this;
    }

    public function __clone(): void
    {
        foreach ($this->expressions as $key => $e) {
            if (is_object($e)) {
                $this->expressions[$key] = clone $e;
            }
        }
    }
}
