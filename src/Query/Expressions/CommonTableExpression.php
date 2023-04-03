<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedExpressionInterfaceFromClosure;
use Somnambulist\Components\QueryBuilder\Query\Expression;

/**
 * An expression that represents a common table expression definition.
 */
class CommonTableExpression implements Expression
{
    protected IdentifierExpression $name;

    /**
     * @var array<IdentifierExpression>
     */
    protected array $fields = [];

    protected ?Expression $query = null;

    /**
     * Whether the CTE is materialized or not materialized.
     *
     * @var string|null
     */
    protected ?string $materialized = null;

    protected bool $recursive = false;

    public function __construct(string $name = '', Expression|Closure|null $query = null)
    {
        $this->name = new IdentifierExpression($name);

        if ($query) {
            $this->query($query);
        }
    }

    /**
     * Alias of `name()` to allow `with()->query()->as()` syntax
     */
    public function as(string $name): self
    {
        return $this->name($name);
    }

    /**
     * Sets the name of this CTE.
     *
     * This is the name you used to reference the expression in select, insert, etc queries.
     *
     * @param string $name The CTE name.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = new IdentifierExpression($name);

        return $this;
    }

    /**
     * Sets the query for this CTE.
     *
     * @param Expression|Closure $query CTE query
     *
     * @return $this
     */
    public function query(Expression|Closure $query): self
    {
        if ($query instanceof Closure) {
            $query = $query();

            if (!$query instanceof Expression) {
                throw ExpectedExpressionInterfaceFromClosure::create($query);
            }
        }

        $this->query = $query;

        return $this;
    }

    /**
     * Adds one or more fields (arguments) to the CTE.
     *
     * @param IdentifierExpression|string ...$field Field names
     *
     * @return $this
     */
    public function field(IdentifierExpression|string ...$field): self
    {
        foreach ($field as &$f) {
            if (!$f instanceof IdentifierExpression) {
                $f = new IdentifierExpression($f);
            }
        }

        $this->fields = array_merge($this->fields, $field);

        return $this;
    }

    /**
     * Sets this CTE as materialized.
     *
     * @return $this
     */
    public function materialized(): self
    {
        $this->materialized = 'MATERIALIZED';

        return $this;
    }

    /**
     * Sets this CTE as not materialized.
     *
     * @return $this
     */
    public function notMaterialized(): self
    {
        $this->materialized = 'NOT MATERIALIZED';

        return $this;
    }

    /**
     * Gets whether this CTE is recursive.
     *
     * @return bool
     */
    public function isRecursive(): bool
    {
        return $this->recursive;
    }

    /**
     * Sets this CTE as recursive.
     *
     * @return $this
     */
    public function recursive(): self
    {
        $this->recursive = true;

        return $this;
    }

    public function getName(): IdentifierExpression
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getQuery(): ?Expression
    {
        return $this->query;
    }

    public function getMaterialized(): ?string
    {
        return $this->materialized;
    }

    public function isMaterialized(): bool
    {
        return !is_null($this->materialized);
    }

    public function traverse(Closure $callback): self
    {
        $callback($this->name);

        foreach ($this->fields as $field) {
            $callback($field);
            $field->traverse($callback);
        }

        if ($this->query) {
            $callback($this->query);
            $this->query->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        $this->name = clone $this->name;

        if ($this->query) {
            $this->query = clone $this->query;
        }

        foreach ($this->fields as $key => $field) {
            $this->fields[$key] = clone $field;
        }
    }
}
