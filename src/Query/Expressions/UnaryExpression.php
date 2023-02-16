<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Query\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Query\ExpressionInterface;

/**
 * An expression object that represents an expression with only a single operand.
 */
class UnaryExpression implements ExpressionInterface
{
    /**
     * Indicates that the operation is in pre-order
     *
     * @var int
     */
    public const PREFIX = 0;

    /**
     * Indicates that the operation is in post-order
     *
     * @var int
     */
    public const POSTFIX = 1;

    /**
     * The operator this unary expression represents
     *
     * @var string
     */
    protected string $operator;

    /**
     * Holds the value which the unary expression operates
     *
     * @var mixed
     */
    protected mixed $value;

    /**
     * Where to place the operator
     *
     * @var int
     */
    protected int $position;

    /**
     * @param string $operator The operator to used for the expression
     * @param mixed $value the value to use as the operand for the expression
     * @param int $position either UnaryExpression::PREFIX or UnaryExpression::POSTFIX
     */
    public function __construct(string $operator, mixed $value, int $position = self::PREFIX)
    {
        $this->operator = $operator;
        $this->value = $value;
        $this->position = $position;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function traverse(Closure $callback): self
    {
        if ($this->value instanceof ExpressionInterface) {
            $callback($this->value);
            $this->value->traverse($callback);
        }

        return $this;
    }

    public function __clone()
    {
        if ($this->value instanceof ExpressionInterface) {
            $this->value = clone $this->value;
        }
    }
}
