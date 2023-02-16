<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidValueForExpression;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\TypeMap;

/**
 * Represents a SQL when/then clause with a fluid API
 */
class WhenThenExpression implements ExpressionInterface
{
    use CaseExpressionTrait;

    /**
     * The names of the clauses that are valid for use with the `clause()` method.
     *
     * @var array<string>
     */
    protected array $validClauseNames = [
        'when',
        'then',
    ];

    protected TypeMap $typeMap;

    /**
     * Then `WHEN` value.
     *
     * @var ExpressionInterface|object|scalar|null
     */
    protected mixed $when = null;

    /**
     * The `WHEN` value type.
     *
     * @var array|string|null
     */
    protected array|string|null $whenType = null;

    /**
     * The `THEN` value.
     *
     * @var ExpressionInterface|object|scalar|null
     */
    protected mixed $then = null;

    /**
     * Whether the `THEN` value has been defined, eg whether `then()` has been invoked.
     *
     * @var bool
     */
    protected bool $hasThenBeenDefined = false;

    /**
     * The `THEN` result type.
     *
     * @var string|null
     */
    protected ?string $thenType = null;

    public function __construct(?TypeMap $typeMap = null)
    {
        $this->typeMap = $typeMap ?? new TypeMap();
    }

    /**
     * Sets the `WHEN` value.
     *
     * @param object|array|string|float|int|bool $when The `WHEN` value. When using an array of
     *  conditions, it must be compatible with `Query::where()`. Note that this argument is _not_
     *  completely safe for use with user data, as a user supplied array would allow for raw SQL to slip in! If you
     *  plan to use user data, either pass a single type for the `$type` argument (which forces the `$when` value to be
     *  a non-array, and then always binds the data), use a conditions array where the user data is only passed on the
     *  value side of the array entries, or custom bindings!
     * @param array<string, string>|string|null $type The when value type. Either an associative array when using array
     *     style conditions, or else a string. If no type is provided, the type will be tried to be inferred from the
     *     value.
     *
     * @return $this
     * @throws InvalidArgumentException In case the `$when` argument is an empty array.
     * @throws InvalidArgumentException In case the `$when` argument is an array, and the `$type` argument is neither
     * an array, nor null.
     * @throws InvalidArgumentException In case the `$when` argument is a non-array value, and the `$type` argument is
     * neither a string, nor null.
     * @see CaseStatementExpression::when() for a more detailed usage explanation.
     */
    public function when(object|array|string|float|int|bool $when, array|string|null $type = null): self
    {
        if (is_array($when)) {
            if (empty($when)) {
                throw new InvalidArgumentException('The "$when" argument must be a non-empty array when passing an array');
            }

            if ($type !== null && !is_array($type)) {
                throw new InvalidArgumentException(sprintf(
                    'When using an array for the "$when" argument, the "$type" argument must be an array too, "%s" given.',
                    get_debug_type($type)
                ));
            }

            // avoid dirtying the type map for possible consecutive `when()` calls
            $typeMap = clone $this->typeMap;
            if (is_array($type) && count($type) > 0) {
                $typeMap = $typeMap->setTypes($type);
            }

            $when = new QueryExpression($when, $typeMap);
        } else {
            if ($type !== null && !is_string($type)) {
                throw new InvalidArgumentException(sprintf(
                    'When using a non-array value for the "$when" argument, the "$type" argument must be a string, "%s" given.',
                    get_debug_type($type)
                ));
            }

            if ($type === null && !$when instanceof ExpressionInterface) {
                $type = $this->inferType($when);
            }
        }

        $this->when = $when;
        $this->whenType = $type;

        return $this;
    }

    /**
     * Sets the `THEN` result value.
     *
     * @param ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type. If no type is provided, the type will be inferred from the given
     *  result value.
     *
     * @return $this
     */
    public function then(mixed $result, ?string $type = null): self
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ($result !== null && !is_scalar($result) && !(is_object($result) && !($result instanceof Closure))) {
            throw InvalidValueForExpression::argumentNotAllowed($result);
        }

        $this->then = $result;
        $this->thenType = $type ?? $this->inferType($result);
        $this->hasThenBeenDefined = true;

        return $this;
    }

    /**
     * Returns the expression's result value type.
     *
     * @return string|null
     * @see WhenThenExpression::then()
     */
    public function getResultType(): ?string
    {
        return $this->thenType;
    }

    /**
     * Returns the available data for the given clause.
     *
     * ### Available clauses
     *
     * The following clause names are available:
     *
     * * `when`: The `WHEN` value.
     * * `then`: The `THEN` result value.
     *
     * @param string $clause The name of the clause to obtain.
     *
     * @return ExpressionInterface|object|scalar|null
     * @throws InvalidArgumentException In case the given clause name is invalid.
     */
    public function clause(string $clause): mixed
    {
        if (!in_array($clause, $this->validClauseNames, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The "$clause" argument must be one of "%s", the given value "%s" is invalid.',
                    implode('", "', $this->validClauseNames),
                    $clause
                )
            );
        }

        return $this->{$clause};
    }

    public function getTypeMap(): TypeMap
    {
        return $this->typeMap;
    }

    public function getWhen(): mixed
    {
        return $this->when;
    }

    public function getWhenType(): array|string|null
    {
        return $this->whenType;
    }

    public function getThen(): mixed
    {
        return $this->then;
    }

    public function hasThenBeenDefined(): bool
    {
        return $this->hasThenBeenDefined;
    }

    public function getThenType(): ?string
    {
        return $this->thenType;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->when instanceof ExpressionInterface) {
            $callback($this->when);
            $this->when->traverse($callback);
        }

        if ($this->then instanceof ExpressionInterface) {
            $callback($this->then);
            $this->then->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->when instanceof ExpressionInterface) {
            $this->when = clone $this->when;
        }

        if ($this->then instanceof ExpressionInterface) {
            $this->then = clone $this->then;
        }
    }
}
