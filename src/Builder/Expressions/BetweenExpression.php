<?php declare(strict_types=1);

namespace Somnambulist\Components\QueryBuilder\Builder\Expressions;

use Closure;
use Somnambulist\Components\QueryBuilder\Builder\ExpressionInterface;
use Somnambulist\Components\QueryBuilder\Builder\FieldInterface;
use Somnambulist\Components\QueryBuilder\TypeCaster;

/**
 * An expression object that represents a SQL BETWEEN snippet
 */
class BetweenExpression implements ExpressionInterface, FieldInterface
{
    public function __construct(
        protected ExpressionInterface|string $field,
        protected mixed $from,
        protected mixed $to,
        protected ?string $type = null
    ) {
        $this->from = TypeCaster::castTo($this->from, $this->type);
        $this->to = TypeCaster::castTo($this->to, $this->type);
    }

    public function getField(): ExpressionInterface|array|string
    {
        return $this->field;
    }

    public function setField(ExpressionInterface|array|string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getFrom(): mixed
    {
        return $this->from;
    }

    public function setFrom(mixed $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): mixed
    {
        return $this->to;
    }

    public function setTo(mixed $to): self
    {
        $this->to = $to;

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

    public function traverse(Closure $callback): self
    {
        foreach ([$this->field, $this->from, $this->to] as $part) {
            if ($part instanceof ExpressionInterface) {
                $callback($part);
            }
        }

        return $this;
    }

    public function __clone()
    {
        foreach (['field', 'from', 'to'] as $part) {
            if ($this->{$part} instanceof ExpressionInterface) {
                $this->{$part} = clone $this->{$part};
            }
        }
    }
}
