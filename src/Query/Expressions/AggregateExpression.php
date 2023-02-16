<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Query\Query;
use Somnambulist\Components\QueryBuilder\Query\WindowInterface;

/**
 * This represents an SQL aggregate function expression in an SQL statement.
 *
 * Calls can be constructed by passing the name of the function and a list of params.
 * For security reasons, all params passed are quoted by default unless explicitly
 * told otherwise.
 */
class AggregateExpression extends FunctionExpression implements WindowInterface
{
    protected ?QueryExpression $filter = null;
    protected ?WindowExpression $window = null;

    /**
     * Adds conditions to the FILTER clause. The conditions are the same format as `Query::where()`.
     *
     * @param ExpressionInterface|Closure|array|string $conditions
     * @param array<string, string> $types
     *
     * @return $this
     * @see Query::where()
     */
    public function filter(ExpressionInterface|Closure|array|string $conditions, array $types = []): self
    {
        $this->filter ??= new QueryExpression();

        if ($conditions instanceof Closure) {
            $conditions = $conditions(new QueryExpression());
        }

        $this->filter->add($conditions, $types);

        return $this;
    }

    /**
     * Adds an empty `OVER()` window expression or a named window expression.
     *
     * @param string|null $name Window name
     *
     * @return $this
     */
    public function over(?string $name = null): self
    {
        $window = $this->window();

        if ($name) {
            $window->name($name);
        }

        return $this;
    }

    public function partition(ExpressionInterface|Closure|array|string $partitions): self
    {
        $this->window()->partition($partitions);

        return $this;
    }

    public function orderBy(ExpressionInterface|Closure|array|string $fields): self
    {
        $this->window()->orderBy($fields);

        return $this;
    }

    public function range(ExpressionInterface|string|int|null $start, ExpressionInterface|string|int|null $end = 0): self
    {
        $this->window()->range($start, $end);

        return $this;
    }

    public function rows(?int $start, ?int $end = 0): self
    {
        $this->window()->rows($start, $end);

        return $this;
    }

    public function groups(?int $start, ?int $end = 0): self
    {
        $this->window()->groups($start, $end);

        return $this;
    }

    public function frame(
        string $type,
        ExpressionInterface|string|int|null $startOffset,
        string $startDirection,
        ExpressionInterface|string|int|null $endOffset,
        string $endDirection
    ): self
    {
        $this->window()->frame($type, $startOffset, $startDirection, $endOffset, $endDirection);

        return $this;
    }

    public function excludeCurrent(): self
    {
        $this->window()->excludeCurrent();

        return $this;
    }

    public function excludeGroup(): self
    {
        $this->window()->excludeGroup();

        return $this;
    }

    public function excludeTies(): self
    {
        $this->window()->excludeTies();

        return $this;
    }

    public function getFilter(): ?QueryExpression
    {
        return $this->filter;
    }

    public function getWindow(): ?WindowExpression
    {
        return $this->window;
    }

    /**
     * Returns or creates WindowExpression for function.
     *
     * @return WindowExpression
     */
    protected function window(): WindowExpression
    {
        return $this->window ??= new WindowExpression();
    }

    public function traverse(Closure $callback): self
    {
        parent::traverse($callback);

        if ($this->filter !== null) {
            $callback($this->filter);
            $this->filter->traverse($callback);
        }
        if ($this->window !== null) {
            $callback($this->window);
            $this->window->traverse($callback);
        }

        return $this;
    }

    public function count(): int
    {
        $count = parent::count();

        if ($this->window !== null) {
            $count = $count + 1;
        }

        return $count;
    }

    public function __clone()
    {
        parent::__clone();

        if ($this->filter !== null) {
            $this->filter = clone $this->filter;
        }
        if ($this->window !== null) {
            $this->window = clone $this->window;
        }
    }
}
