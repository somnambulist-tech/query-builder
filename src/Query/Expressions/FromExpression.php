<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use ArrayIterator;
use Closure;
use Countable;
use IteratorAggregate;
use Somnambulist\Components\QueryBuilder\Exceptions\QueryException;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Traversable;
use function array_key_exists;
use function count;
use function is_null;
use function is_string;

class FromExpression implements Countable, ExpressionInterface, IteratorAggregate
{
    /**
     * @var array<string, ExpressionInterface>
     */
    private array $tables;

    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->tables);
    }

    public function count(): int
    {
        return count($this->tables);
    }

    public function add(ExpressionInterface|string $table, string $as = null): self
    {
        if ($table instanceof Query && is_null($as)) {
            throw QueryException::fromQueryRequiresAlias();
        }
        if (is_string($table)) {
            $table = new IdentifierExpression($table);
        }

        $i = $this->count();
        $key = $as ?: $i++;

        $this->tables[$key] = $table;

        return $this;
    }

    /**
     * Returns true if the table is part of the current from expression or is a named alias
     *
     * @param string $table
     *
     * @return bool
     */
    public function references(string $table): bool
    {
        foreach ($this as $key => $value) {
            if (is_string($key) && $key === $table) {
                return true;
            }
            if ($value instanceof IdentifierExpression) {
                if ($value->getIdentifier() === $table) {
                    return true;
                }
            }
        }

        return false;
    }

    public function has(string $alias): bool
    {
        return array_key_exists($alias, $this->tables);
    }

    public function get(string $alias): ExpressionInterface
    {
        return $this->tables[$alias] ?? throw QueryException::noFromClauseFor($alias);
    }

    public function remove(string $alias): self
    {
        unset($this->tables[$alias]);

        return $this;
    }

    public function traverse(Closure $callback): ExpressionInterface
    {
        foreach ($this->tables as $join) {
            $callback($join);
            $join->traverse($callback);
        }

        return $this;
    }

    public function __clone(): void
    {
        foreach ($this->tables as $key => $join) {
            $this->tables[$key] = clone $join;
        }
    }
}
