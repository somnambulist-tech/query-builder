<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use InvalidArgumentException;
use LogicException;
use Somnambulist\Components\QueryBuilder\Builder\HasReturnTypeInterface;
use Somnambulist\Components\QueryBuilder\Exceptions\ExpectedWhenThenExpressionFromClosure;
use Somnambulist\Components\QueryBuilder\Exceptions\InvalidCaseUsageBuildingStatement;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\TypeMap;
use function func_num_args;

/**
 * Represents a SQL CASE statement with a fluid API
 */
class CaseStatementExpression implements ExpressionInterface, HasReturnTypeInterface
{
    use CaseExpressionTrait;

    /**
     * The names of the clauses that are valid for use with the `clause()` method.
     *
     * @var array<string>
     */
    protected array $validClauseNames = [
        'value',
        'when',
        'else',
    ];

    /**
     * Whether this is a simple case expression.
     *
     * @var bool
     */
    protected bool $isSimpleVariant = false;

    /**
     * The case value.
     *
     * @var ExpressionInterface|object|scalar|null
     */
    protected mixed $value = null;

    /**
     * The case value type.
     *
     * @var string|null
     */
    protected ?string $valueType = null;

    /**
     * The `WHEN ... THEN ...` expressions.
     *
     * @var array<WhenThenExpression>
     */
    protected array $when = [];

    /**
     * Buffer that holds values and types for use with `then()`.
     *
     * @var array|null
     */
    protected ?array $whenBuffer = null;

    /**
     * The else part result value.
     *
     * @var ExpressionInterface|object|scalar|null
     */
    protected mixed $else = null;

    /**
     * The else part result type.
     *
     * @var string|null
     */
    protected ?string $elseType = null;

    /**
     * The return type.
     *
     * @var string|null
     */
    protected ?string $returnType = null;

    protected TypeMap $typeMap;

    /**
     * When a value is set, the syntax generated is `CASE case_value WHEN when_value ... END` (simple case),
     * where the `when_value`'s are compared against the `case_value`.
     *
     * When no value is set, the syntax generated is `CASE WHEN when_conditions ... END` (searched case),
     * where the conditions hold the comparisons.
     *
     * Note that `null` is a valid case value, and thus should only be passed if you actually want to create
     * the simple case expression variant!
     *
     * @param ExpressionInterface|object|scalar|null $value The case value.
     * @param string|null $type The case value type.
     */
    public function __construct(mixed $value = null, ?string $type = null)
    {
        if (func_num_args() > 0) {
            if (!is_null($value) && !is_scalar($value) && !(is_object($value) && !($value instanceof Closure))) {
                throw new InvalidArgumentException(sprintf(
                    'The "$value" argument must be either "null", a scalar value, an object, or an instance of "%s", "%s" given.',
                    ExpressionInterface::class,
                    get_debug_type($value)
                ));
            }

            $this->value = $value;

            if ($value !== null && $type === null && !($value instanceof ExpressionInterface)) {
                $type = $this->inferType($value);
            }

            $this->valueType = $type;
            $this->isSimpleVariant = true;
        }

        $this->typeMap = new TypeMap();
    }

    public function isSimpleVariant(): bool
    {
        return $this->isSimpleVariant;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getValueType(): ?string
    {
        return $this->valueType;
    }

    public function getWhen(): array
    {
        return $this->when;
    }

    public function getElse(): mixed
    {
        return $this->else;
    }

    public function getElseType(): ?string
    {
        return $this->elseType;
    }

    public function getTypeMap(): TypeMap
    {
        return $this->typeMap;
    }

    public function setTypeMap(TypeMap $typeMap): self
    {
        $this->typeMap = $typeMap;

        return $this;
    }

    public function hasActiveWhenBuffer(): bool
    {
        return !empty($this->whenBuffer);
    }

    /**
     * Sets the `WHEN` value for a `WHEN ... THEN ...` expression, or a
     * self-contained expression that holds both the value for `WHEN`
     * and the value for `THEN`.
     *
     * ### Order based syntax
     *
     * When passing a value other than a self-contained
     * `WhenThenExpression`,
     * instance, the `WHEN ... THEN ...` statement must be closed off with
     * a call to `then()` before invoking `when()` again or `else()`:
     *
     * ```
     * $queryExpression
     *     ->case($query->identifier('Table.column'))
     *     ->when(true)
     *     ->then('Yes')
     *     ->when(false)
     *     ->then('No')
     *     ->else('Maybe');
     * ```
     *
     * ### Self-contained expressions
     *
     * When passing an instance of `WhenThenExpression`,
     * being it directly, or via a callable, then there is no need to close
     * using `then()` on this object, instead the statement will be closed
     * on the `WhenThenExpression`
     * object using
     * `WhenThenExpression::then()`.
     *
     * Callables will receive an instance of `WhenThenExpression`,
     * and must return one, being it the same object, or a custom one:
     *
     * ```
     * $queryExpression
     *     ->case()
     *     ->when(function (WhenThenExpression $whenThen) {
     *         return $whenThen
     *             ->when(['Table.column' => true])
     *             ->then('Yes');
     *     })
     *     ->when(function (WhenThenExpression $whenThen) {
     *         return $whenThen
     *             ->when(['Table.column' => false])
     *             ->then('No');
     *     })
     *     ->else('Maybe');
     * ```
     *
     * ### Type handling
     *
     * The types provided via the `$type` argument will be merged with the
     * type map set for this expression. When using callables for `$when`,
     * the `WhenThenExpression`
     * instance received by the callables will inherit that type map, however
     * the types passed here will _not_ be merged in case of using callables,
     * instead the types must be passed in
     * `WhenThenExpression::when()`:
     *
     * ```
     * $queryExpression
     *     ->case()
     *     ->when(function (WhenThenExpression $whenThen) {
     *         return $whenThen
     *             ->when(['unmapped_column' => true], ['unmapped_column' => 'bool'])
     *             ->then('Yes');
     *     })
     *     ->when(function (WhenThenExpression $whenThen) {
     *         return $whenThen
     *             ->when(['unmapped_column' => false], ['unmapped_column' => 'bool'])
     *             ->then('No');
     *     })
     *     ->else('Maybe');
     * ```
     *
     * ### User data safety
     *
     * When passing user data, be aware that allowing a user defined array
     * to be passed, is a potential SQL injection vulnerability, as it
     * allows for raw SQL to slip in!
     *
     * The following is _unsafe_ usage that must be avoided:
     *
     * ```
     * $case
     *      ->when($userData)
     * ```
     *
     * A safe variant for the above would be to define a single type for
     * the value:
     *
     * ```
     * $case
     *      ->when($userData, 'integer')
     * ```
     *
     * This way an exception would be triggered when an array is passed for
     * the value, thus preventing raw SQL from slipping in, and all other
     * types of values would be forced to be bound as an integer.
     *
     * Another way to safely pass user data is when using a conditions
     * array, and passing user data only on the value side of the array
     * entries, which will cause them to be bound:
     *
     * ```
     * $case
     *      ->when([
     *          'Table.column' => $userData,
     *      ])
     * ```
     *
     * Lastly, data can also be bound manually:
     *
     * ```
     * $query
     *      ->select([
     *          'val' => $query->newExpr()
     *              ->case()
     *              ->when($query->newExpr(':userData'))
     *              ->then(123)
     *      ])
     *      ->bind(':userData', $userData, 'integer')
     * ```
     *
     * @param ExpressionInterface|Closure|object|array|scalar $when The `WHEN` value. When using an
     *  array of conditions, it must be compatible with `Query::where()`. Note that this argument is
     *  _not_ completely safe for use with user data, as a user supplied array would allow for raw SQL to slip in! If
     *  you plan to use user data, either pass a single type for the `$type` argument (which forces the `$when` value
     *  to be a non-array, and then always binds the data), use a conditions array where the user data is only passed
     *  on the value side of the array entries, or custom bindings!
     * @param array<string, string>|string|null $type If not provided, will be inferred during compilation
     *
     * @return $this
     * @throws LogicException In case this a closing `then()` call is required before calling this method.
     * @throws LogicException In case the callable doesn't return an instance of `WhenThenExpression`.
     */
    public function when(mixed $when, array|string|null $type = null): self
    {
        if ($this->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::when();
        }

        if ($when instanceof Closure) {
            $when = $when(new WhenThenExpression($this->typeMap));

            if (!$when instanceof WhenThenExpression) {
                throw ExpectedWhenThenExpressionFromClosure::create($when);
            }
        }

        if ($when instanceof WhenThenExpression) {
            $this->when[] = $when;
        } else {
            $this->whenBuffer = ['when' => $when, 'type' => $type];
        }

        return $this;
    }

    /**
     * Sets the `THEN` result value for the last `WHEN ... THEN ...`
     * statement that was opened using `when()`.
     *
     * ### Order based syntax
     *
     * This method can only be invoked in case `when()` was previously
     * used with a value other than a closure or an instance of
     * `WhenThenExpression`:
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->then('Yes')
     *     ->when(['Table.column' => false])
     *     ->then('No')
     *     ->else('Maybe');
     * ```
     *
     * The following would all fail with an exception:
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->when(['Table.column' => false])
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->else('Maybe')
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->then('Yes')
     *     // ...
     * ```
     *
     * ```
     * $case
     *     ->when(['Table.column' => true])
     *     ->then('Yes')
     *     ->then('No')
     *     // ...
     * ```
     *
     * @param ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type, will be inferred during compilation if missing
     *
     * @return $this
     * @throws LogicException In case `when()` wasn't previously called with a value other than a closure or an
     *  instance of `WhenThenExpression`.
     */
    public function then(mixed $result, ?string $type = null): self
    {
        if (!$this->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::then();
        }

        $whenThen = (new WhenThenExpression($this->typeMap))
            ->when($this->whenBuffer['when'], $this->whenBuffer['type'])
            ->then($result, $type)
        ;

        $this->whenBuffer = null;

        $this->when[] = $whenThen;

        return $this;
    }

    /**
     * Sets the `ELSE` result value.
     *
     * @param ExpressionInterface|object|scalar|null $result The result value.
     * @param string|null $type The result type. If no type is provided, the type will be tried to be inferred from the
     *  value.
     *
     * @return $this
     * @throws LogicException In case a closing `then()` call is required before calling this method.
     * @throws InvalidArgumentException In case the `$result` argument is neither a scalar value, nor an object, an
     *  instance of `ExpressionInterface`, or `null`.
     */
    public function else(mixed $result, ?string $type = null): self
    {
        if ($this->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::else();
        }

        if ($result !== null && !is_scalar($result) && !(is_object($result) && !($result instanceof Closure))) {
            throw new InvalidArgumentException(sprintf(
                'The "$result" argument must be either "null", a scalar value, an object, or an instance of "%s", "%s" given.',
                ExpressionInterface::class,
                get_debug_type($result)
            ));
        }

        $type ??= $this->inferType($result);

        $this->else = $result;
        $this->elseType = $type;

        return $this;
    }

    /**
     * Returns the abstract type that this expression will return.
     *
     * If no type has been explicitly set via `setReturnType()`, this
     * method will try to obtain the type from the result types of the
     * `then()` and `else() `calls. All types must be identical in order
     * for this to work, otherwise the type will default to `string`.
     *
     * @return string
     * @see CaseStatementExpression::then()
     */
    public function getReturnType(): string
    {
        if ($this->returnType !== null) {
            return $this->returnType;
        }

        $types = [];

        foreach ($this->when as $when) {
            $type = $when->getResultType();

            if ($type !== null) {
                $types[] = $type;
            }
        }

        if ($this->elseType !== null) {
            $types[] = $this->elseType;
        }

        $types = array_unique($types);

        if (count($types) === 1) {
            return $types[0];
        }

        return 'string';
    }

    /**
     * Sets the abstract type that this expression will return.
     *
     * If no type is being explicitly set via this method, then the
     * `getReturnType()` method will try to infer the type from the
     * result types of the `then()` and `else() `calls.
     *
     * @param string $type The type name to use.
     *
     * @return $this
     */
    public function setReturnType(string $type): self
    {
        $this->returnType = $type;

        return $this;
    }

    /**
     * Returns the available data for the given clause.
     *
     * ### Available clauses
     *
     * The following clause names are available:
     *
     * * `value`: The case value for a `CASE case_value WHEN ...` expression.
     * * `when`: An array of `WHEN ... THEN ...` expressions.
     * * `else`: The `ELSE` result value.
     *
     * @param string $clause The name of the clause to obtain.
     *
     * @return ExpressionInterface|object|array<WhenThenExpression>|scalar|null
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

    public function traverse(Closure $callback): self
    {
        if ($this->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::incomplete();
        }

        if ($this->value instanceof ExpressionInterface) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        foreach ($this->when as $when) {
            $callback($when);
            $when->traverse($callback);
        }

        if ($this->else instanceof ExpressionInterface) {
            $callback($this->else);
            $this->else->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->hasActiveWhenBuffer()) {
            throw InvalidCaseUsageBuildingStatement::incomplete();
        }

        if ($this->value instanceof ExpressionInterface) {
            $this->value = clone $this->value;
        }

        foreach ($this->when as $key => $when) {
            $this->when[$key] = clone $this->when[$key];
        }

        if ($this->else instanceof ExpressionInterface) {
            $this->else = clone $this->else;
        }
    }
}
