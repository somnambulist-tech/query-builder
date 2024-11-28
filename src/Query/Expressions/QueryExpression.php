<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Countable;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\StringConditionParser;
use Somnambulist\Components\QueryBuilder\TypeMap;
use function strtoupper;

/**
 * Represents a SQL Query expression. Internally it stores a tree of
 * expressions that can be compiled by converting this object to string
 * and will contain a correctly parenthesized and nested expression.
 */
class QueryExpression implements Expression, Countable
{
    /**
     * String to be used for joining each of the internal expressions
     * this object internally stores for example "AND", "OR", etc.
     *
     * @var string
     */
    protected string $conjunction;

    /**
     * A list of strings or other expression objects that represent the "branches" of
     * the expression tree. For example one key of the array might look like "sum > :value"
     *
     * @var array
     */
    protected array $conditions = [];

    protected TypeMap $typeMap;
    protected StringConditionParser $parser;

    /**
     * A new expression object can be created without any params and be built dynamically.
     *
     * Otherwise, it is possible to pass an array of conditions containing either a tree-like array structure
     * to be parsed and/or other expression objects. Optionally, you can set the conjunction keyword to be used
     * for joining each part of this level of the expression tree.
     *
     * @param Expression|array|string $conditions Tree like array structure
     * containing all the conditions to be added or nested inside this expression object.
     * @param TypeMap|null $types Associative array of types to be associated with the values
     * passed in $conditions.
     * @param string $conjunction the glue that will join all the string conditions at this
     * level of the expression tree. For example "AND", "OR", "XOR"...
     *
     * @see QueryExpression::add() for more details on $conditions and $types
     */
    public function __construct(
        Expression|array|string $conditions = [],
        ?TypeMap $types = null,
        string $conjunction = 'AND'
    )
    {
        $this->typeMap = $types ?? new TypeMap();
        $this->parser = new StringConditionParser($this->typeMap, $conjunction);
        $this->useConjunction($conjunction);

        if (!empty($conditions)) {
            $this->add($conditions, $this->typeMap->getTypes());
        }
    }

    public function useConjunction(string $conjunction): self
    {
        $this->conjunction = strtoupper($conjunction);

        return $this;
    }

    public function getConjunction(): string
    {
        return $this->conjunction;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getTypeMap(): TypeMap
    {
        return $this->typeMap;
    }

    /**
     * Adds one or more conditions to this expression object. Conditions can be
     * expressed in a one dimensional array, that will cause all conditions to
     * be added directly at this level of the tree, or they can be nested arbitrarily
     * making it create more expression objects that will be nested inside and
     * configured to use the specified conjunction.
     *
     * If the type passed for any of the fields is expressed "type[]" (note braces)
     * then it will cause the placeholder to be re-written dynamically so if the
     * value is an array, it will create as many placeholders as values are in it.
     */
    public function add(Expression|array|string $conditions, array $types = []): self
    {
        if (is_string($conditions)) {
            $this->conditions[] = $conditions;

            return $this;
        }

        if ($conditions instanceof Expression) {
            $this->conditions[] = $conditions;

            return $this;
        }

        $this->addConditions($conditions, $types);

        return $this;
    }

    /**
     * Adds a new condition to the expression object in the form "field = value".
     */
    public function eq(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '='));
    }

    /**
     * Adds a new condition to the expression object in the form "field != value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     * If it is suffixed with "[]" and the value is an array then multiple placeholders
     * will be created, one per each value in the array.
     *
     * @return $this
     */
    public function notEq(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '!='));
    }

    /**
     * Adds a new condition to the expression object in the form "field > value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function gt(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '>'));
    }

    /**
     * Adds a new condition to the expression object in the form "field < value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function lt(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '<'));
    }

    /**
     * Adds a new condition to the expression object in the form "field >= value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function gte(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '>='));
    }

    /**
     * Adds a new condition to the expression object in the form "field <= value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function lte(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, '<='));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NULL".
     *
     * @param Expression|string $field database field to be tested for null
     *
     * @return $this
     */
    public function isNull(Expression|string $field): self
    {
        if (!$field instanceof Expression) {
            $field = new IdentifierExpression($field);
        }

        return $this->add(new UnaryExpression('IS NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field IS NOT NULL".
     *
     * @param Expression|string $field database field to be
     * tested for not null
     *
     * @return $this
     */
    public function isNotNull(Expression|string $field): self
    {
        if (!($field instanceof Expression)) {
            $field = new IdentifierExpression($field);
        }

        return $this->add(new UnaryExpression('IS NOT NULL', $field, UnaryExpression::POSTFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "field LIKE value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function like(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field ILIKE value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function likeCaseInsensitive(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'ILIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT LIKE value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function notLike(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'NOT LIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT ILIKE value".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param mixed $value The value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function notLikeCaseInsensitive(Expression|string $field, mixed $value, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new ComparisonExpression($field, $value, $type, 'NOT ILIKE'));
    }

    /**
     * Adds a new condition to the expression object in the form "field IN (value1, value2)".
     *
     * @param Expression|string $field
     * @param Expression|array $values
     * @param string|null $type
     *
     * @return $this
     */
    public function in(
        Expression|string $field,
        Expression|array $values,
        ?string $type = null
    ): self
    {
        $type ??= $this->calculateType($field);
        $type = $type ?: 'string';
        $type .= '[]';

        return $this->add(new ComparisonExpression($field, $values, $type, 'IN'));
    }

    /**
     * Returns a new case expression object.
     *
     * When a value is set, the syntax generated is `CASE case_value WHEN when_value ... END` (simple case),
     * where the `when_value`'s are compared against the `case_value`.
     *
     * When no value is set, the syntax generated is `CASE WHEN when_conditions ... END` (searched case),
     * where the conditions hold the comparisons.
     *
     * Note that `null` is a valid case value, and thus should only be passed if you actually want to create the simple
     * case expression variant!
     *
     * @param Expression|object|scalar|null $value
     * @param string|null $type
     *
     * @return CaseStatementExpression
     */
    public function case(mixed $value = null, ?string $type = null): CaseStatementExpression
    {
        if (func_num_args() > 0) {
            $expression = new CaseStatementExpression($value, $type);
        } else {
            $expression = new CaseStatementExpression();
        }

        $expression->setTypeMap($this->typeMap);

        return $expression;
    }

    /**
     * Adds a new condition to the expression object in the form "field NOT IN (value1, value2)".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param Expression|array $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function notIn(
        Expression|string $field,
        Expression|array $values,
        ?string $type = null
    ): self
    {
        $type ??= $this->calculateType($field);
        $type = $type ?: 'string';
        $type .= '[]';

        return $this->add(new ComparisonExpression($field, $values, $type, 'NOT IN'));
    }

    /**
     * Adds a new condition to the expression object in the form "(field NOT IN (value1, value2) OR field IS NULL".
     *
     * @param Expression|string $field Database field to be compared against value
     * @param Expression|array $values the value to be bound to $field for comparison
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function notInOrNull(
        Expression|string $field,
        Expression|array $values,
        ?string $type = null
    ): self
    {
        $or = new self(conjunction: 'OR');
        $or
            ->notIn($field, $values, $type)
            ->isNull($field)
        ;

        return $this->add($or);
    }

    /**
     * Adds a new condition to the expression object in the form "EXISTS (...)".
     *
     * @param Expression $expression the inner query
     *
     * @return $this
     */
    public function exists(Expression $expression): self
    {
        return $this->add(new UnaryExpression('EXISTS', $expression, UnaryExpression::PREFIX));
    }

    /**
     * Adds a new condition to the expression object in the form "NOT EXISTS (...)".
     *
     * @param Expression $expression the inner query
     *
     * @return $this
     */
    public function notExists(Expression $expression): self
    {
        return $this->add(new UnaryExpression('NOT EXISTS', $expression, UnaryExpression::PREFIX));
    }

    /**
     * Adds a new condition to the expression object in the form
     * "field BETWEEN from AND to".
     *
     * @param Expression|string $field The field name to compare for values in between the range.
     * @param mixed $from The initial value of the range.
     * @param mixed $to The ending value in the comparison range.
     * @param string|null $type the type name for $value as configured using the Type map.
     *
     * @return $this
     */
    public function between(Expression|string $field, mixed $from, mixed $to, ?string $type = null): self
    {
        $type ??= $this->calculateType($field);

        return $this->add(new BetweenExpression($field, $from, $to, $type));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "AND"
     *
     * @param Expression|Closure|array|string $conditions to be joined with AND
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     *
     * @return static
     */
    public function and(Expression|Closure|array|string $conditions, array $types = []): static
    {
        if ($conditions instanceof Closure) {
            return $conditions(new static([], $this->typeMap->setTypes($types)));
        }

        return new static($conditions, $this->typeMap->setTypes($types));
    }

    /**
     * Returns a new QueryExpression object containing all the conditions passed
     * and set up the conjunction to be "OR"
     *
     * @param Expression|Closure|array|string $conditions to be joined with OR
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     *
     * @return static
     */
    public function or(Expression|Closure|array|string $conditions, array $types = []): static
    {
        if ($conditions instanceof Closure) {
            return $conditions(new static([], $this->typeMap->setTypes($types), 'OR'));
        }

        return new static($conditions, $this->typeMap->setTypes($types), 'OR');
    }

    /**
     * Adds a new set of conditions to this level of the tree and negates
     * the final result by prepending a NOT, it will look like
     * "NOT ( (condition1) AND (conditions2) )" conjunction depends on the one
     * currently configured for this object.
     *
     * @param Expression|Closure|array|string $conditions to be added and negated
     * @param array<string, string> $types Associative array of fields pointing to the type of the
     * values that are being passed. Used for correctly binding values to statements.
     *
     * @return $this
     */
    public function not(Expression|Closure|array|string $conditions, array $types = []): self
    {
        return $this->add(['NOT' => $conditions], $types);
    }

    /**
     * Returns the number of internal conditions that are stored in this expression.
     * Useful to determine if this expression object is void, or it will generate
     * a non-empty string when compiled
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->conditions);
    }

    /**
     * Builds equal condition or assignment with identifier wrapping.
     *
     * @param string $leftField Left join condition field name.
     * @param string $rightField Right join condition field name.
     *
     * @return $this
     */
    public function equalFields(string $leftField, string $rightField): self
    {
        $wrapIdentifier = function ($field) {
            if ($field instanceof Expression) {
                return $field;
            }

            return new IdentifierExpression($field);
        };

        return $this->eq($wrapIdentifier($leftField), $wrapIdentifier($rightField));
    }

    public function traverse(Closure $callback): self
    {
        foreach ($this->conditions as $c) {
            if ($c instanceof Expression) {
                $callback($c);
                $c->traverse($callback);
            }
        }

        return $this;
    }

    /**
     * Executes a callback for each of the parts that form this expression.
     *
     * The callback is required to return a value with which the currently
     * visited part will be replaced. If the callback returns null then
     * the part will be discarded completely from this expression.
     *
     * The callback function will receive each of the conditions as first param and
     * the key as second param. It is possible to declare the second parameter as
     * passed by reference, this will enable you to change the key under which the
     * modified part is stored.
     *
     * @param Closure $callback The callback to run for each part
     *
     * @return $this
     */
    public function iterateParts(Closure $callback): self
    {
        $parts = [];

        foreach ($this->conditions as $k => $c) {
            $key = &$k;
            $part = $callback($c, $key);

            if ($part !== null) {
                $parts[$key] = $part;
            }
        }

        $this->conditions = $parts;

        return $this;
    }

    /**
     * Returns true if this expression contains any other nested ExpressionInterface objects
     *
     * @return bool
     */
    public function hasNestedExpression(): bool
    {
        foreach ($this->conditions as $c) {
            if ($c instanceof Expression) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auxiliary function used for decomposing a nested array of conditions and build
     * a tree structure inside this object to represent the full SQL expression.
     * String conditions are stored directly in the conditions, while any other
     * representation is wrapped around an adequate instance or of this class.
     *
     * @param array $conditions list of conditions to be stored in this object
     * @param array<int|string, string> $types list of types associated on fields referenced in $conditions
     *
     * @return void
     */
    protected function addConditions(array $conditions, array $types): void
    {
        $operators = ['and', 'or', 'xor'];

        $typeMap = $this->typeMap->setTypes($types);

        foreach ($conditions as $k => $c) {
            $numericKey = is_numeric($k);

            if ($c instanceof Closure) {
                $expr = new static([], $typeMap);
                $c = $c($expr, $this);
            }

            if ($numericKey && empty($c)) {
                continue;
            }

            $isArray = is_array($c);
            $isOperator = $isNot = false;

            if (!$numericKey) {
                $normalizedKey = strtolower($k);
                $isOperator = in_array($normalizedKey, $operators);
                $isNot = $normalizedKey === 'not';
            }

            if (($isOperator || $isNot) && ($isArray || $c instanceof Countable) && count($c) === 0) {
                continue;
            }

            if ($numericKey && $c instanceof Expression) {
                $this->conditions[] = $c;
                continue;
            }

            if ($numericKey && is_string($c)) {
                $this->conditions[] = $c;
                continue;
            }

            if ($numericKey && $isArray || $isOperator) {
                $this->conditions[] = new static($c, $typeMap, $numericKey ? 'AND' : $k);
                continue;
            }

            if ($isNot) {
                $this->conditions[] = new UnaryExpression('NOT', new static($c, $typeMap));
                continue;
            }

            if (!$numericKey) {
                $this->conditions[] = $this->parseCondition($k, $c);
            }
        }
    }

    /**
     * Parses a string conditions by trying to extract the operator inside it if any
     *
     * @param string $condition
     * @param mixed $value
     *
     * @return Expression|string
     * @throws InvalidArgumentException
     */
    protected function parseCondition(string $condition, mixed $value): Expression|string
    {
        return $this->parser->parse($condition, $value);
    }

    /**
     * Allows replacing the string parser used to deconstruct WHERE expressions of the form `field EQ :value`
     *
     * Note: the current type map and conjunction are passed to the parser, overriding any existing options.
     * In most cases this should not be needed, and is included as a means to change the behaviour without
     * requiring reflection or extension of the QueryExpression class.
     *
     * @param StringConditionParser $parser
     *
     * @return $this
     * @internal
     */
    public function setParser(StringConditionParser $parser): self
    {
        $this->parser = $parser->setTypeMap($this->typeMap)->setConjunction($this->conjunction);

        return $this;
    }

    /**
     * Returns the type name for the passed field if it was stored in the typeMap
     *
     * @param Expression|string $field The field name to get a type for.
     *
     * @return string|null The computed type or null, if the type is unknown.
     */
    protected function calculateType(Expression|string $field): ?string
    {
        $field = $field instanceof IdentifierExpression ? $field->getIdentifier() : $field;

        if (!is_string($field)) {
            return null;
        }

        return $this->typeMap->type($field);
    }
    
    public function __clone()
    {
        foreach ($this->conditions as $i => $condition) {
            if ($condition instanceof Expression) {
                $this->conditions[$i] = clone $condition;
            }
        }
    }
}
