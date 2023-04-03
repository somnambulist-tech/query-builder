<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\Expression;
use Somnambulist\Components\QueryBuilder\Query\Field;
use Somnambulist\Components\QueryBuilder\TypeCasterManager;
use function str_contains;

/**
 * A Comparison is a type of query expression that represents an operation
 * involving a field an operator and a value. In its most common form the
 * string representation of a comparison is `field = value`
 */
class ComparisonExpression implements Expression, Field
{
    protected bool $isMultiple = false;
    protected array $valueExpressions = [];

    public function __construct(
        protected Expression|array|string $field,
        protected mixed $value,
        protected ?string $type = null,
        protected string $operator = '='
    ) {
        $this->setValue($value);
    }

    public function getField(): string|array|Expression
    {
        return $this->field;
    }

    public function field(string|array|Expression $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $value = TypeCasterManager::castTo($value, $this->type);
        $isMultiple = $this->type && str_contains($this->type, '[]');

        if ($isMultiple) {
            [$value, $this->valueExpressions] = $this->collectExpressions($value);
        }

        $this->isMultiple = $isMultiple;
        $this->value = $value;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function setOperator(string $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function getValueExpressions(): array
    {
        return $this->valueExpressions;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->field instanceof Expression) {
            $callback($this->field);
            $this->field->traverse($callback);
        }

        if ($this->value instanceof Expression) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        foreach ($this->valueExpressions as $v) {
            $callback($v);
            $v->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        foreach (['value', 'field'] as $prop) {
            if ($this->{$prop} instanceof Expression) {
                $this->{$prop} = clone $this->{$prop};
            }
        }
    }

    /**
     * Returns an array with the original $values in the first position
     * and all ExpressionInterface objects that could be found in the second
     * position.
     *
     * @param Expression|iterable $values The rows to insert
     *
     * @return array
     */
    protected function collectExpressions(Expression|iterable $values): array
    {
        if ($values instanceof Expression) {
            return [$values, []];
        }

        $expressions = $result = [];
        $isArray = is_array($values);

        if ($isArray) {
            $result = (array)$values;
        }

        foreach ($values as $k => $v) {
            if ($v instanceof Expression) {
                $expressions[$k] = $v;
            }

            if ($isArray) {
                $result[$k] = $v;
            }
        }

        return [$result, $expressions];
    }
}
