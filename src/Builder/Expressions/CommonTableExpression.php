<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedExpressionInterfaceFromClosure;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;

/**
 * An expression that represents a common table expression definition.
 */
class CommonTableExpression implements ExpressionInterface
{
    /**
     * The CTE name.
     *
     * @var IdentifierExpression
     */
    protected IdentifierExpression $name;

    /**
     * The field names to use for the CTE.
     *
     * @var array<IdentifierExpression>
     */
    protected array $fields = [];

    /**
     * The CTE query definition.
     *
     * @var ExpressionInterface|null
     */
    protected ?ExpressionInterface $query = null;

    /**
     * Whether the CTE is materialized or not materialized.
     *
     * @var string|null
     */
    protected ?string $materialized = null;

    /**
     * Whether the CTE is recursive.
     *
     * @var bool
     */
    protected bool $recursive = false;

    public function __construct(string $name = '', ExpressionInterface|Closure|null $query = null)
    {
        $this->name = new IdentifierExpression($name);

        if ($query) {
            $this->query($query);
        }
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
     * @param ExpressionInterface|Closure $query CTE query
     *
     * @return $this
     */
    public function query(ExpressionInterface|Closure $query): self
    {
        if ($query instanceof Closure) {
            $query = $query();

            if (!$query instanceof ExpressionInterface) {
                throw ExpectedExpressionInterfaceFromClosure::create($query);
            }
        }

        $this->query = $query;

        return $this;
    }

    /**
     * Adds one or more fields (arguments) to the CTE.
     *
     * @param IdentifierExpression|array<IdentifierExpression>|array<string>|string $fields Field names
     *
     * @return $this
     */
    public function field(IdentifierExpression|array|string $fields): self
    {
        $fields = (array)$fields;

        foreach ($fields as &$field) {
            if (!$field instanceof IdentifierExpression) {
                $field = new IdentifierExpression($field);
            }
        }

        $this->fields = array_merge($this->fields, $fields);

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

    public function getQuery(): ?ExpressionInterface
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
