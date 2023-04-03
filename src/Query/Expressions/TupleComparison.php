<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use InvalidArgumentException;
use Somnambulist\Components\QueryBuilder\Query\Expression;

/**
 * This expression represents SQL fragments that are used for comparing one tuple
 * to another, one tuple to a set of other tuples or one tuple to an expression
 */
class TupleComparison extends ComparisonExpression
{
    /**
     * The type to be used for casting the value to a database representation
     *
     * @var array<string|null>
     * @psalm-suppress NonInvariantDocblockPropertyType
     */
    protected array $types;

    public function __construct(
        Expression|array|string $fields,
        Expression|array $values,
        array $types = [],
        string $conjunction = '='
    ) {
        parent::__construct($fields, null, null, $conjunction);

        $this->types = $types;
        $this->setValue($values);
    }

    /**
     * Returns the type to be used for casting the value to a database representation
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function setValue(mixed $value): self
    {
        if ($this->isMulti()) {
            if (is_array($value) && !is_array(current($value))) {
                throw new InvalidArgumentException(
                    'Multi-tuple comparisons require a multi-tuple value, single-tuple given.'
                );
            }
        } else {
            if (is_array($value) && is_array(current($value))) {
                throw new InvalidArgumentException(
                    'Single-tuple comparisons require a single-tuple value, multi-tuple given.'
                );
            }
        }

        $this->value = $value;

        return $this;
    }

    public function traverse(Closure $callback): self
    {
        $fields = (array)$this->getField();

        foreach ($fields as $field) {
            $this->traverseValue($field, $callback);
        }

        $value = $this->getValue();

        if ($value instanceof Expression) {
            $callback($value);
            $value->traverse($callback);

            return $this;
        }

        foreach ($value as $val) {
            if ($this->isMulti()) {
                foreach ($val as $v) {
                    $this->traverseValue($v, $callback);
                }
            } else {
                $this->traverseValue($val, $callback);
            }
        }

        return $this;
    }

    /**
     * Conditionally executes the callback for the passed value if it is an ExpressionInterface
     *
     * @param mixed $value The value to traverse
     * @param Closure $callback The callback to use when traversing
     *
     * @return void
     */
    protected function traverseValue(mixed $value, Closure $callback): void
    {
        if ($value instanceof Expression) {
            $callback($value);
            $value->traverse($callback);
        }
    }

    /**
     * Determines if each of the values in this expression is a tuple in itself
     *
     * @return bool
     */
    public function isMulti(): bool
    {
        return in_array(strtolower($this->operator), ['in', 'not in']);
    }
}
